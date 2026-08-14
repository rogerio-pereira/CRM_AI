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

        $user = User::factory()
                    ->make([
                        'email' => 'operator@example.com',
                    ]);

        $canViewHorizon = Gate::forUser($user)
                              ->allows('viewHorizon');

        $this->assertTrue($canViewHorizon);
    }

    public function test_horizon_gate_restricts_to_allowlisted_emails_outside_local(): void
    {
        config([
                        'horizon.allowed_emails' => 'allowed@example.com',
        ]);

        $this->app['env'] = 'production';

        $allowed = User::factory()
                        ->make([
                            'email' => 'allowed@example.com',
                        ]);

        $denied = User::factory()
                        ->make([
                            'email' => 'other@example.com',
                        ]);

        $allowedCanViewHorizon = Gate::forUser($allowed)
                                     ->allows('viewHorizon');

        $deniedCanViewHorizon = Gate::forUser($denied)
                                    ->allows('viewHorizon');

        $this->assertTrue($allowedCanViewHorizon);
        $this->assertFalse($deniedCanViewHorizon);
    }

    public function test_horizon_gate_denies_when_allowlist_is_empty_outside_local(): void
    {
        config([
                        'horizon.allowed_emails' => '',
        ]);

        $this->app['env'] = 'production';

        $user = User::factory()
                    ->make([
                        'email' => 'any@example.com',
                    ]);

        $canViewHorizon = Gate::forUser($user)
                              ->allows('viewHorizon');

        $this->assertFalse($canViewHorizon);
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

        $user = User::factory()
                    ->make([
                        'email' => null,
                    ]);

        $canViewHorizon = Gate::forUser($user)
                              ->allows('viewHorizon');

        $this->assertFalse($canViewHorizon);
    }
}
