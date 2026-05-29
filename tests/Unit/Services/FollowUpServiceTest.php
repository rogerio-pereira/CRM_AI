<?php

namespace Tests\Unit\Services;

use App\Enums\FollowUpPriority;
use App\Enums\FollowUpReminderStatus;
use App\Events\FollowUpCreated;
use App\Events\FollowUpUpdated;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Opportunity;
use App\Services\FollowUpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FollowUpServiceTest extends TestCase
{
    use RefreshDatabase;

    private FollowUpService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(FollowUpService::class);
    }

    public function test_create_sets_pending_status_and_dispatches_event(): void
    {
        Event::fake([FollowUpCreated::class]);

        $client = Client::factory()->create();
        $opportunity = Opportunity::factory()->for($client)->create();

        $followUp = $this->service->create([
            'client_id' => $client->id,
            'opportunity_id' => $opportunity->id,
            'due_at' => now()->addDay(),
            'priority' => FollowUpPriority::High->value,
            'notes' => 'Call client',
        ]);

        $this->assertSame(FollowUpReminderStatus::Pending, $followUp->reminder_status);
        $this->assertSame($opportunity->id, $followUp->opportunity_id);

        Event::assertDispatched(FollowUpCreated::class);
    }

    public function test_create_rejects_opportunity_from_another_client(): void
    {
        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $opportunity = Opportunity::factory()->for($otherClient)->create();

        $this->expectException(ValidationException::class);

        $this->service->create([
            'client_id' => $client->id,
            'opportunity_id' => $opportunity->id,
            'due_at' => now()->addDay(),
            'priority' => FollowUpPriority::Medium->value,
        ]);
    }

    public function test_update_persists_attributes_and_dispatches_event(): void
    {
        Event::fake([FollowUpUpdated::class]);

        $client = Client::factory()->create();
        $followUp = FollowUp::factory()->for($client)->create([
            'notes' => 'Original notes',
        ]);

        $updated = $this->service->update($followUp, [
            'due_at' => now()->addDays(2),
            'priority' => FollowUpPriority::Low->value,
            'notes' => 'Updated notes',
        ]);

        $this->assertSame('Updated notes', $updated->notes);
        $this->assertSame(FollowUpPriority::Low, $updated->priority);

        Event::assertDispatched(FollowUpUpdated::class);
    }

    public function test_update_rejects_opportunity_from_another_client(): void
    {
        $client = Client::factory()->create();
        $otherClient = Client::factory()->create();
        $followUp = FollowUp::factory()->for($client)->create();
        $foreignOpportunity = Opportunity::factory()->for($otherClient)->create();

        $this->expectException(ValidationException::class);

        $this->service->update($followUp, [
            'opportunity_id' => $foreignOpportunity->id,
        ]);
    }

    public function test_create_allows_missing_or_null_opportunity_id(): void
    {
        Event::fake([FollowUpCreated::class]);

        $client = Client::factory()->create();

        $withoutKey = $this->service->create([
            'client_id' => $client->id,
            'due_at' => now()->addDay(),
            'priority' => FollowUpPriority::Medium->value,
        ]);

        $withNull = $this->service->create([
            'client_id' => $client->id,
            'opportunity_id' => null,
            'due_at' => now()->addDays(2),
            'priority' => FollowUpPriority::Medium->value,
        ]);

        $this->assertNull($withoutKey->opportunity_id);
        $this->assertNull($withNull->opportunity_id);
    }

    public function test_assert_opportunity_belongs_to_client_skips_when_opportunity_record_is_missing(): void
    {
        $method = new \ReflectionMethod($this->service, 'assertOpportunityBelongsToClient');
        $method->setAccessible(true);

        $method->invoke($this->service, [
            'client_id' => 1,
            'opportunity_id' => 99999,
        ]);

        $this->assertTrue(true);
    }

    public function test_assert_opportunity_belongs_to_client_skips_when_opportunity_id_key_is_absent(): void
    {
        $method = new \ReflectionMethod($this->service, 'assertOpportunityBelongsToClient');
        $method->setAccessible(true);

        $method->invoke($this->service, [
            'client_id' => 1,
            'due_at' => now()->addDay(),
        ]);

        $this->assertTrue(true);
    }

    public function test_mark_complete_sets_status_and_clears_snooze(): void
    {
        Event::fake([FollowUpUpdated::class]);

        $followUp = FollowUp::factory()->snoozed()->create();

        $result = $this->service->markComplete($followUp);

        $this->assertSame(FollowUpReminderStatus::Completed, $result->reminder_status);
        $this->assertNotNull($result->completed_at);
        $this->assertNull($result->snoozed_until);

        Event::assertDispatched(FollowUpUpdated::class);
    }

    public function test_snooze_uses_custom_until_date(): void
    {
        Event::fake([FollowUpUpdated::class]);

        $followUp = FollowUp::factory()->create();
        $until = Carbon::parse('2030-06-15 10:00:00');

        $result = $this->service->snooze($followUp, $until);

        $this->assertSame(FollowUpReminderStatus::Snoozed, $result->reminder_status);
        $this->assertTrue($result->snoozed_until->equalTo($until));

        Event::assertDispatched(FollowUpUpdated::class);
    }

    public function test_list_for_index_filters_by_search_priority_and_overdue(): void
    {
        $matchingClient = Client::factory()->create(['company_name' => 'Acme Searchable Co']);
        $otherClient = Client::factory()->create(['company_name' => 'Other Corp']);

        $matching = FollowUp::factory()->for($matchingClient)->create([
            'priority' => FollowUpPriority::High,
            'due_at' => now()->subDay(),
        ]);

        FollowUp::factory()->for($otherClient)->create([
            'priority' => FollowUpPriority::Low,
            'due_at' => now()->addDay(),
        ]);

        $searchResults = $this->service->paginateForIndex('acme', null, false);

        $this->assertCount(1, $searchResults);
        $this->assertTrue($searchResults->first()->is($matching));

        $priorityResults = $this->service->paginateForIndex(null, FollowUpPriority::High->value, false);

        $this->assertCount(1, $priorityResults);
        $this->assertTrue($priorityResults->first()->is($matching));

        $overdueResults = $this->service->paginateForIndex(null, null, true);

        $this->assertCount(1, $overdueResults);
        $this->assertTrue($overdueResults->first()->is($matching));
    }

    public function test_list_for_index_returns_all_when_filters_are_empty(): void
    {
        FollowUp::factory()->count(3)->create();

        $results = $this->service->paginateForIndex(null, 'all', false);

        $this->assertCount(3, $results);
    }

    public function test_paginate_for_index_returns_twenty_items_per_page(): void
    {
        FollowUp::factory()->count(21)->create();

        $pageOne = $this->service->paginateForIndex(null, null, false);
        $pageTwo = $this->service->paginateForIndex(null, null, false, page: 2);

        $this->assertSame(21, $pageOne->total());
        $this->assertCount(20, $pageOne->items());
        $this->assertTrue($pageOne->hasMorePages());
        $this->assertCount(1, $pageTwo->items());
    }
}
