<?php

use App\Enums\TaskStatus;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;

it('displays the tasks page and creates a task', function () {
    $user = User::factory()
                ->create();
    $client = Client::factory()
                    ->create(['company_name' => 'Task Browser Co']);
    $opportunity = Opportunity::factory()
                        ->for($client)
                        ->create(['title' => 'Linked Deal']);

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

    $task = Task::where('title', 'Browser task title')
                ->first();

    expect($task)
        ->not
        ->toBeNull();
    expect($task->is_important)
        ->toBeTrue();
});

it('marks a task done from the actions menu', function () {
    $user = User::factory()
                ->create();
    $client = Client::factory()
                    ->create(['company_name' => 'Done Browser Co']);
    $dueAt = Carbon::now()
                    ->addDay();
    $task = Task::factory()
                ->for($client)
                ->create([
                    'title' => 'Complete me',
                    'due_at' => $dueAt,
                ]);

    $this->actingAs($user);

    visit('/tasks')
        ->assertSee('Complete me')
        ->click('@tasks-actions-'.$task->id)
        ->click('@tasks-complete-'.$task->id)
        ->uncheck('@tasks-hide-done')
        ->assertPresent('[data-test="tasks-row-'.$task->id.'"][data-done-row="true"]')
        ->assertPresent('[data-test="tasks-status-badge-'.$task->id.'"][data-status="done"]');

    $freshTask = $task->fresh();

    expect($freshTask->status)
        ->toBe(TaskStatus::Done);
});

it('deletes a task from the delete modal', function () {
    $user = User::factory()
                ->create();
    $task = Task::factory()
                ->create(['title' => 'Delete Browser Task']);

    $this->actingAs($user);

    visit('/tasks')
        ->assertSee('Delete Browser Task')
        ->click('@tasks-actions-'.$task->id)
        ->click('@tasks-delete-'.$task->id)
        ->click('@tasks-delete-confirm')
        ->assertDontSee('Delete Browser Task');

    $deletedTask = Task::find($task->id);

    expect($deletedTask)
        ->toBeNull();
});
