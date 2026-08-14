<?php

use App\Models\User;

it('runs smoke checks for all public web routes without JavaScript errors', function () {
    $pages = visit([
        '/',
    ]);

    $pages->assertNoSmoke();
});

it('runs smoke checks for auth routes without JavaScript errors', function () {
    $pages = visit([
        '/login',
        '/register',
        '/forgot-password',
    ]);

    $pages->assertNoSmoke();
});

it('runs smoke checks for authenticated web routes without JavaScript errors', function () {
    $user = User::factory()
                ->create();

    $this->actingAs($user);

    $pages = visit([
        '/dashboard',
        '/leads',
        '/opportunities',
        '/follow-ups',
        '/tasks',
        '/services',
        '/settings/profile',
        '/settings/security',
    ]);

    $pages->assertNoSmoke();
});
