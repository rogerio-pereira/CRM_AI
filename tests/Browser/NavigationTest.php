<?php

use App\Models\User;

/**
 * @return array<string, array{nav: string, path: string, marker: string}>
 */
function crmSidebarRoutes(): array
{
    return [
        'dashboard' => [
            'nav' => 'nav-dashboard',
            'path' => '/dashboard',
            'marker' => 'dashboard-page',
        ],
        'leads' => [
            'nav' => 'nav-leads',
            'path' => '/leads',
            'marker' => 'leads-page',
        ],
        'opportunities' => [
            'nav' => 'nav-opportunities',
            'path' => '/opportunities',
            'marker' => 'opportunities-page',
        ],
        'follow-ups' => [
            'nav' => 'nav-follow-ups',
            'path' => '/follow-ups',
            'marker' => 'follow-ups-page',
        ],
        'tasks' => [
            'nav' => 'nav-tasks',
            'path' => '/tasks',
            'marker' => 'tasks-page',
        ],
    ];
}

it('navigates primary CRM routes via the sidebar on desktop', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $page = visit('/dashboard')->on()->desktop();

    foreach (crmSidebarRoutes() as $route) {
        $page->click('@'.$route['nav'])
            ->assertPathIs($route['path'])
            ->assertPresent('[data-test="'.$route['marker'].'"]')
            ->assertPresent('[data-test="'.$route['nav'].'"][data-current]');
    }
});

it('does not expose settings in the primary sidebar navigation', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit('/dashboard')
        ->assertNoSmoke()
        ->assertMissing('[data-test="nav-settings"]');
});

it('opens profile settings from the sidebar user menu on desktop', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit('/dashboard')
        ->on()
        ->desktop()
        ->click('@sidebar-menu-button')
        ->click('Settings')
        ->assertPathIs('/settings/profile')
        ->assertSee('Update your name and email address');
});

it('navigates via the sidebar when collapsed on desktop', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit('/dashboard')
        ->on()
        ->desktop()
        ->click('[data-flux-sidebar-collapse]')
        ->assertPresent('[data-flux-sidebar-collapsed-desktop]')
        ->click('@nav-tasks')
        ->assertPathIs('/tasks')
        ->assertPresent('[data-test="nav-tasks"][data-current]');
});

it('returns to the dashboard when clicking the sidebar brand', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit('/leads')
        ->on()
        ->desktop()
        ->click('[data-flux-sidebar-brand]')
        ->assertPathIs('/dashboard')
        ->assertPresent('[data-test="dashboard-page"]');
});

it('shows the mobile menu toggle without a header user menu', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit('/dashboard')
        ->on()
        ->mobile()
        ->assertPresent('[data-test="mobile-menu-toggle"]')
        ->assertNotPresent('[data-test="app-header"] [data-flux-profile]');
});

it('opens the sidebar and navigates on mobile', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit('/dashboard')
        ->on()
        ->mobile()
        ->click('@mobile-menu-toggle')
        ->click('@nav-opportunities')
        ->assertPathIs('/opportunities')
        ->assertPresent('[data-test="opportunities-page"]')
        ->assertSee('Opportunities');
});

it('navigates between profile and security settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit('/user/confirm-password')
        ->fill('password', 'password')
        ->click('@confirm-password-button')
        ->assertNoSmoke();

    visit('/settings/profile')
        ->assertPathIs('/settings/profile')
        ->click('@settings-nav-security')
        ->assertPathIs('/settings/security')
        ->assertSee('Update password')
        ->click('@settings-nav-profile')
        ->assertPathIs('/settings/profile')
        ->assertSee('Update your name and email address');
});
