<?php

use App\Models\User;

it('visits primary CRM navigation routes without JavaScript errors', function () {
    $user = User::factory()
                ->create();

    $this->actingAs($user);

    $pages = visit([
        '/dashboard',
        '/leads',
        '/opportunities',
        '/follow-ups',
        '/tasks',
        '/settings/profile',
        '/settings/security',
    ]);

    $pages->assertNoSmoke();
});

it('highlights the active sidebar item for the current route', function () {
    $user = User::factory()
                ->create();

    $this->actingAs($user);

    $page = visit('/leads');

    $page->assertNoSmoke()
        ->assertPresent('[data-test="nav-leads"][data-current]');
});

it('can collapse the sidebar on desktop', function () {
    $user = User::factory()
                ->create();

    $this->actingAs($user);

    $page = visit('/dashboard')
                ->on()
                ->desktop();

    $page->assertNoSmoke()
        ->click('[data-flux-sidebar-collapse]')
        ->assertPresent('[data-flux-sidebar-collapsed-desktop]')
        ->assertPresent('[data-test="nav-dashboard"]')
        ->assertPresent('[data-test="nav-leads"]');
});
