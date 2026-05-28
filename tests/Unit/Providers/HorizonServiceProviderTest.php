<?php

namespace Tests\Unit\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class HorizonServiceProviderTest extends TestCase
{
    public function test_horizon_gate_allows_all_users_in_local_environment(): void
    {
        $this->app['env'] = 'local';

        $user = User::factory()->make([
            'email' => 'operator@example.com',
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('viewHorizon'));
    }

    public function test_horizon_gate_restricts_to_allowlisted_emails_outside_local(): void
    {
        config([
            'horizon.allowed_emails' => 'allowed@example.com',
        ]);

        $this->app['env'] = 'production';

        $allowed = User::factory()->make([
            'email' => 'allowed@example.com',
        ]);

        $denied = User::factory()->make([
            'email' => 'other@example.com',
        ]);

        $this->assertTrue(Gate::forUser($allowed)->allows('viewHorizon'));
        $this->assertFalse(Gate::forUser($denied)->allows('viewHorizon'));
    }

    public function test_horizon_gate_denies_when_allowlist_is_empty_outside_local(): void
    {
        config([
            'horizon.allowed_emails' => '',
        ]);

        $this->app['env'] = 'production';

        $user = User::factory()->make([
            'email' => 'any@example.com',
        ]);

        $this->assertFalse(Gate::forUser($user)->allows('viewHorizon'));
    }

    public function test_horizon_gate_denies_guest_outside_local(): void
    {
        config([
            'horizon.allowed_emails' => 'allowed@example.com',
        ]);

        $this->app['env'] = 'production';

        $this->assertFalse(Gate::allows('viewHorizon'));
    }

    public function test_horizon_gate_denies_user_without_email_outside_local(): void
    {
        config([
            'horizon.allowed_emails' => 'allowed@example.com',
        ]);

        $this->app['env'] = 'production';

        $user = User::factory()->make([
            'email' => null,
        ]);

        $this->assertFalse(Gate::forUser($user)->allows('viewHorizon'));
    }
}
