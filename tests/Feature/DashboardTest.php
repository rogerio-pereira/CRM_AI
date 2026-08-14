<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $dashboardUrl = route('dashboard');
        $loginUrl = route('login');

        $response = $this->get($dashboardUrl);

        $response->assertRedirect($loginUrl);
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()
                    ->create();
        $this->actingAs($user);

        $dashboardUrl = route('dashboard');
        $response = $this->get($dashboardUrl);
        $dailyOverview = __('Daily overview');
        $leadsCreatedToday = __('Leads created today');
        $tasksAndFollowUps = __('Tasks and follow-ups');
        $pendingTasks = __('Pending tasks');

        $response->assertOk();
        $response->assertSee($dailyOverview, false);
        $response->assertSee($leadsCreatedToday, false);
        $response->assertSee($tasksAndFollowUps, false);
        $response->assertSee($pendingTasks, false);
    }
}
