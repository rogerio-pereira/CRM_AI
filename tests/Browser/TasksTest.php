<?php

use App\Enums\TaskStatus;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;

it('displays the tasks page and creates a task', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['company_name' => 'Task Browser Co']);
    $opportunity = Opportunity::factory()->for($client)->create(['title' => 'Linked Deal']);

    $this->actingAs($user);

    visit('/tasks')
        ->assertNoSmoke()
        ->assertPresent('[data-test="tasks-page"]')
        ->click('@tasks-create-button')
        ->fill('@tasks-form-title', 'Browser task title')
        ->select('@tasks-form-client', (string) $client->id)
        ->select('@tasks-form-opportunity', (string) $opportunity->id)
        ->check('@tasks-form-important')
        ->click('@tasks-form-submit')
        ->assertSee('Browser task title')
        ->assertSee('Task Browser Co');

    $task = Task::where('title', 'Browser task title')->first();

    expect($task)->not->toBeNull();
    expect($task->is_important)->toBeTrue();
});

it('marks a task done from the actions menu', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create(['company_name' => 'Done Browser Co']);
    $task = Task::factory()->for($client)->create([
        'title' => 'Complete me',
        'due_at' => now()->addDay(),
    ]);

    $this->actingAs($user);

    visit('/tasks')
        ->assertSee('Complete me')
        ->click('@tasks-actions-'.$task->id)
        ->click('@tasks-complete-'.$task->id)
        ->uncheck('@tasks-hide-done')
        ->assertPresent('[data-test="tasks-row-'.$task->id.'"][data-done-row="true"]')
        ->assertPresent('[data-test="tasks-status-badge-'.$task->id.'"][data-status="done"]');

    expect($task->fresh()->status)->toBe(TaskStatus::Done);
});

it('deletes a task from the delete modal', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['title' => 'Delete Browser Task']);

    $this->actingAs($user);

    visit('/tasks')
        ->assertSee('Delete Browser Task')
        ->click('@tasks-actions-'.$task->id)
        ->click('@tasks-delete-'.$task->id)
        ->click('@tasks-delete-confirm')
        ->assertDontSee('Delete Browser Task');

    expect(Task::find($task->id))->toBeNull();
});
