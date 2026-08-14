<?php

namespace Tests\Feature\Platform;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorizonAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_allowlisted_authenticated_user_can_access_horizon_dashboard(): void
    {
        config([
            'horizon.allowed_emails' => 'operator@example.com',
        ]);

        $this->app['env'] = 'staging';

        $user = User::factory()
                    ->create([
                        'email' => 'operator@example.com',
                    ]);

        $response = $this->actingAs($user)
                        ->get('/horizon');

        $response->assertSuccessful();
    }

    public function test_non_allowlisted_user_cannot_access_horizon_outside_local(): void
    {
        config([
            'horizon.allowed_emails' => 'operator@example.com',
        ]);

        $this->app['env'] = 'staging';

        $user = User::factory()
                    ->create([
                        'email' => 'other@example.com',
                    ]);

        $response = $this->actingAs($user)
                        ->get('/horizon');

        $response->assertForbidden();
    }
}
