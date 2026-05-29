<?php

namespace Tests\Feature\Dashboard;

use App\Enums\FollowUpReminderStatus;
use App\Enums\PipelineStage;
use App\Enums\TaskStatus;
use App\Livewire\Dashboard\Index;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use App\Services\DashboardMetricsService;
use App\Services\DashboardTablesService;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class OperationalDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_excludes_out_of_scope_widgets(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Horizon', false)
            ->assertDontSee('Queue health', false)
            ->assertDontSee('Failed jobs', false)
            ->assertDontSee('AI metrics', false);
    }

    public function test_today_metric_cards_reflect_seeded_counts(): void
    {
        Carbon::setTestNow('2026-05-28 12:00:00');

        Client::factory()->create(['created_at' => now()]);
        Client::factory()->create(['created_at' => now()->subDay()]);

        $client = Client::factory()->create(['created_at' => now()->subDay()]);
        Opportunity::factory()->for($client)->create(['created_at' => now()]);
        Opportunity::factory()->for($client)->create(['created_at' => now()->subDays(2)]);

        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSet('leadsCreatedToday', 1)
            ->assertSet('opportunitiesCreatedToday', 1)
            ->assertSeeHtml('data-test="dashboard-metric-leads-today"')
            ->assertSeeHtml('data-test="dashboard-metric-opportunities-today"');
    }

    public function test_thirty_day_series_has_thirty_points(): void
    {
        Carbon::setTestNow('2026-05-28 12:00:00');

        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test(Index::class);

        $this->assertCount(30, $component->instance()->leadsSeries);
        $this->assertCount(30, $component->instance()->opportunitiesSeries);
        $this->assertCount(30, $component->instance()->salesSeries);
    }

    public function test_sales_series_only_includes_won_opportunities(): void
    {
        Carbon::setTestNow('2026-05-28 12:00:00');

        $client = Client::factory()->create();

        Opportunity::factory()->for($client)->create([
            'stage' => PipelineStage::Won,
            'estimated_value' => 5000,
            'updated_at' => now(),
        ]);

        Opportunity::factory()->for($client)->create([
            'stage' => PipelineStage::Lead,
            'estimated_value' => 9000,
            'updated_at' => now(),
        ]);

        $service = app(DashboardMetricsService::class);
        $series = $service->salesPerDayLast30Days();
        $today = collect($series)->firstWhere('date', now()->toDateString());

        $this->assertNotNull($today);
        $this->assertSame(5000.0, $today['value']);
    }

    public function test_done_tasks_are_excluded_from_dashboard_table(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        Task::factory()->for($client)->create(['title' => 'Still open']);
        Task::factory()->for($client)->done()->create(['title' => 'Already done']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSee('Still open')
            ->assertDontSee('Already done');
    }

    public function test_follow_ups_table_lists_pending_items_only(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['company_name' => 'Pending Follow Co']);

        FollowUp::factory()->for($client)->create([
            'reminder_status' => FollowUpReminderStatus::Pending,
        ]);

        FollowUp::factory()->for($client)->completed()->create([
            'notes' => 'Done follow-up hidden',
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSee('Pending Follow Co')
            ->assertDontSee('Done follow-up hidden');
    }

    public function test_guests_are_redirected_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_dashboard_tasks_table_is_limited_to_ten_rows(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        Task::factory()
            ->count(12)
            ->for($client)
            ->sequence(fn ($sequence) => [
                'due_at' => now()->addHours($sequence->index + 1),
            ])
            ->create();

        $this->actingAs($user);

        $component = Livewire::test(Index::class);

        $this->assertCount(DashboardTablesService::TABLE_LIMIT, $component->instance()->pendingTasks);
        $this->assertSame(2, $component->instance()->pendingTasksOverflow);
    }

    public function test_dashboard_shows_overflow_row_when_more_than_ten_tasks(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        Task::factory()
            ->count(11)
            ->for($client)
            ->sequence(fn ($sequence) => [
                'due_at' => now()->addHours($sequence->index + 1),
            ])
            ->create();

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSeeHtml('data-test="dashboard-tasks-overflow"')
            ->assertSee(__('+:count items', ['count' => 1]), false);
    }

    public function test_dashboard_shows_overflow_row_when_more_than_ten_follow_ups(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        FollowUp::factory()
            ->count(15)
            ->for($client)
            ->sequence(fn ($sequence) => [
                'due_at' => now()->addHours($sequence->index + 1),
            ])
            ->create([
                'reminder_status' => FollowUpReminderStatus::Pending,
            ]);

        $this->actingAs($user);

        $component = Livewire::test(Index::class);

        $this->assertCount(DashboardTablesService::TABLE_LIMIT, $component->instance()->actionableFollowUps);

        $component
            ->assertSet('actionableFollowUpsOverflow', 5)
            ->assertSeeHtml('data-test="dashboard-follow-ups-overflow"')
            ->assertSee(__('+:count items', ['count' => 5]), false);
    }

    public function test_dashboard_hides_overflow_row_when_ten_or_fewer_items(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        Task::factory()->count(10)->for($client)->create();
        FollowUp::factory()->count(10)->for($client)->create([
            'reminder_status' => FollowUpReminderStatus::Pending,
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->assertSet('pendingTasksOverflow', 0)
            ->assertSet('actionableFollowUpsOverflow', 0)
            ->assertDontSeeHtml('data-test="dashboard-tasks-overflow"')
            ->assertDontSeeHtml('data-test="dashboard-follow-ups-overflow"');
    }

    public function test_dashboard_can_mark_task_done_from_table(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $task = Task::factory()->for($client)->create(['title' => 'Complete from dashboard']);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('markDone', $task->id)
            ->assertDontSee('Complete from dashboard');

        $this->assertSame(TaskStatus::Done, $task->fresh()->status);
    }

    public function test_dashboard_can_mark_follow_up_complete_from_table(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['company_name' => 'Complete Follow Co']);

        $followUp = FollowUp::factory()->for($client)->create([
            'reminder_status' => FollowUpReminderStatus::Pending,
        ]);

        $this->actingAs($user);

        Livewire::test(Index::class)
            ->call('markComplete', $followUp->id)
            ->assertDontSee('Complete Follow Co');

        $this->assertSame(FollowUpReminderStatus::Completed, $followUp->fresh()->reminder_status);
    }

    public function test_pending_for_dashboard_uses_table_limit_constant(): void
    {
        $client = Client::factory()->create();

        Task::factory()
            ->count(TaskService::DASHBOARD_PENDING_LIMIT + 3)
            ->for($client)
            ->create();

        $tasks = app(TaskService::class)->pendingForDashboard();

        $this->assertCount(TaskService::DASHBOARD_PENDING_LIMIT, $tasks);
    }
}
