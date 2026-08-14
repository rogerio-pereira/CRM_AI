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

        $client = Client::factory()
                        ->create();
        $opportunity = Opportunity::factory()
                            ->for($client)
                            ->create();
        $dueAt = now()
                        ->addDay();

        $followUp = $this->service->create([
            'client_id' => $client->id,
            'opportunity_id' => $opportunity->id,
            'due_at' => $dueAt,
            'priority' => FollowUpPriority::High->value,
            'notes' => 'Call client',
        ]);

        $this->assertSame(FollowUpReminderStatus::Pending, $followUp->reminder_status);
        $this->assertSame($opportunity->id, $followUp->opportunity_id);

        Event::assertDispatched(FollowUpCreated::class);
    }

    public function test_create_rejects_opportunity_from_another_client(): void
    {
        $client = Client::factory()
                        ->create();

        $otherClient = Client::factory()
                    ->create();
        $opportunity = Opportunity::factory()
                            ->for($otherClient)
                            ->create();
        $dueAt = now()
                        ->addDay();

        $this->expectException(ValidationException::class);

        $this->service->create([
            'client_id' => $client->id,
            'opportunity_id' => $opportunity->id,
            'due_at' => $dueAt,
            'priority' => FollowUpPriority::Medium->value,
        ]);
    }

    public function test_update_persists_attributes_and_dispatches_event(): void
    {
        Event::fake([FollowUpUpdated::class]);

        $client = Client::factory()
                        ->create();
        $followUp = FollowUp::factory()
                        ->for($client)
                        ->create([
                            'notes' => 'Original notes',
                        ]);
        $dueAt = now()
                        ->addDays(2);

        $updated = $this->service->update($followUp, [
                            'due_at' => $dueAt,
                            'priority' => FollowUpPriority::Low->value,
                            'notes' => 'Updated notes',
        ]);

        $this->assertSame('Updated notes', $updated->notes);
        $this->assertSame(FollowUpPriority::Low, $updated->priority);

        Event::assertDispatched(FollowUpUpdated::class);
    }

    public function test_update_rejects_opportunity_from_another_client(): void
    {
        $client = Client::factory()
                        ->create();

        $otherClient = Client::factory()
                    ->create();
        $followUp = FollowUp::factory()
                        ->for($client)
                        ->create();

        $foreignOpportunity = Opportunity::factory()
                    ->for($otherClient)
                    ->create();

        $this->expectException(ValidationException::class);

        $this->service->update($followUp, [
                            'opportunity_id' => $foreignOpportunity->id,
        ]);
    }

    public function test_create_allows_missing_or_null_opportunity_id(): void
    {
        Event::fake([FollowUpCreated::class]);

        $client = Client::factory()
                        ->create();
        $withoutKeyDueAt = now()
                                ->addDay();
        $withNullDueAt = now()
                                ->addDays(2);

        $withoutKey = $this->service->create([
                            'client_id' => $client->id,
                            'due_at' => $withoutKeyDueAt,
                            'priority' => FollowUpPriority::Medium->value,
        ]);

        $withNull = $this->service->create([
                            'client_id' => $client->id,
                            'opportunity_id' => null,
                            'due_at' => $withNullDueAt,
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
        $dueAt = now()
                        ->addDay();

        $method->invoke($this->service, [
                            'client_id' => 1,
                            'due_at' => $dueAt,
        ]);

        $this->assertTrue(true);
    }

    public function test_mark_complete_sets_status_and_completed_at(): void
    {
        Event::fake([FollowUpUpdated::class]);

        $followUp = FollowUp::factory()
                        ->create();

        $result = $this->service->markComplete($followUp);

        $this->assertSame(FollowUpReminderStatus::Completed, $result->reminder_status);
        $this->assertNotNull($result->completed_at);

        Event::assertDispatched(FollowUpUpdated::class);
    }

    public function test_list_for_index_filters_by_search_priority_and_overdue(): void
    {
        $matchingClient = Client::factory()
                        ->create(['company_name' => 'Acme Searchable Co']);

        $otherClient = Client::factory()
                    ->create(['company_name' => 'Other Corp']);
        $overdueAt = now()
                            ->subDay();
        $futureDueAt = now()
                            ->addDay();

        $matching = FollowUp::factory()
                        ->for($matchingClient)
                        ->create([
                            'priority' => FollowUpPriority::High,
                            'due_at' => $overdueAt,
                        ]);

        FollowUp::factory()
                ->for($otherClient)
                ->create([
                    'priority' => FollowUpPriority::Low,
                    'due_at' => $futureDueAt,
                ]);

        $searchResults = $this->service->paginateForIndex('acme', null, false, false);

        $this->assertCount(1, $searchResults);
        $firstSearchResult = $searchResults->first();

        $this->assertTrue($firstSearchResult->is($matching));

        $priorityResults = $this->service->paginateForIndex(null, FollowUpPriority::High->value, false, false);

        $this->assertCount(1, $priorityResults);
        $firstPriorityResult = $priorityResults->first();

        $this->assertTrue($firstPriorityResult->is($matching));

        $overdueResults = $this->service->paginateForIndex(null, null, true, false);

        $this->assertCount(1, $overdueResults);
        $firstOverdueResult = $overdueResults->first();

        $this->assertTrue($firstOverdueResult->is($matching));
    }

    public function test_paginate_for_index_hides_completed_by_default(): void
    {
        $client = Client::factory()
                        ->create();

        $pending = FollowUp::factory()
                        ->for($client)
                        ->create();

        FollowUp::factory()
                ->for($client)
                ->completed()
                ->create();

        $hidden = $this->service->paginateForIndex(null, null, false, true);
        $visible = $this->service->paginateForIndex(null, null, false, false);

        $this->assertCount(1, $hidden);
        $firstHiddenResult = $hidden->first();

        $this->assertTrue($firstHiddenResult->is($pending));
        $this->assertCount(2, $visible);
    }

    public function test_list_for_index_returns_all_when_filters_are_empty(): void
    {
        FollowUp::factory()
                ->count(3)
                ->create();

        $results = $this->service->paginateForIndex(null, 'all', false, false);

        $this->assertCount(3, $results);
    }

    public function test_paginate_for_index_returns_twenty_items_per_page(): void
    {
        FollowUp::factory()
                ->count(21)
                ->create();

        $pageOne = $this->service->paginateForIndex(null, null, false, false);
        $pageTwo = $this->service->paginateForIndex(null, null, false, false, page: 2);

        $this->assertSame(21, $pageOne->total());
        $this->assertCount(20, $pageOne->items());
        $this->assertTrue($pageOne->hasMorePages());
        $this->assertCount(1, $pageTwo->items());
    }
}
