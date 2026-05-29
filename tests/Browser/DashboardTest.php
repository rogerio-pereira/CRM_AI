<?php

use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Task;
use App\Models\User;

it('displays operational dashboard sections for authenticated users', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['company_name' => 'Dashboard Browser Co']);
    Task::factory()->for($client)->create(['title' => 'Dashboard browser task']);
    FollowUp::factory()->for($client)->create();

    $this->actingAs($user);

    visit('/dashboard')
        ->assertNoSmoke()
        ->assertPresent('[data-test="dashboard-page"]')
        ->assertPresent('[data-test="dashboard-metrics-section"]')
        ->assertPresent('[data-test="dashboard-tables-section"]')
        ->assertPresent('[data-test="dashboard-metric-leads-today"]')
        ->assertPresent('[data-test="dashboard-metric-opportunities-today"]')
        ->assertPresent('[data-test="dashboard-chart-leads"]')
        ->assertPresent('[data-test="dashboard-chart-opportunities"]')
        ->assertPresent('[data-test="dashboard-chart-sales"]')
        ->assertPresent('[data-test="dashboard-table-tasks"]')
        ->assertPresent('[data-test="dashboard-table-follow-ups"]')
        ->assertSee('Dashboard browser task')
        ->assertSee('Dashboard Browser Co');
});
