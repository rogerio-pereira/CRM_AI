<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CrmRouteAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function guestProtectedCrmRoutesProvider(): array
    {
        return [
            'dashboard' => ['/dashboard'],
            'settings profile' => ['/settings/profile'],
            'settings security' => ['/settings/security'],
            'leads' => ['/leads'],
            'opportunities stub' => ['/opportunities'],
            'follow-ups stub' => ['/follow-ups'],
            'tasks' => ['/tasks'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function authenticatedCrmRoutesProvider(): array
    {
        return [
            'dashboard' => ['/dashboard'],
            'settings profile' => ['/settings/profile'],
            'leads' => ['/leads'],
            'opportunities stub' => ['/opportunities'],
            'follow-ups stub' => ['/follow-ups'],
            'tasks' => ['/tasks'],
        ];
    }

    #[DataProvider('guestProtectedCrmRoutesProvider')]
    public function test_guests_are_redirected_from_crm_routes(string $url): void
    {
        $response = $this->get($url);

        $response->assertRedirect(route('login'));
    }

    #[DataProvider('authenticatedCrmRoutesProvider')]
    public function test_authenticated_verified_users_can_access_crm_routes(string $url): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        $response = $this->get($url);

        $response->assertOk();
    }

    public function test_authenticated_users_can_access_security_settings_after_password_confirmation(): void
    {
        $user = User::factory()
                    ->create();

        $response = $this->actingAs($user)
                        ->withSession(['auth.password_confirmed_at' => time()])
                        ->get('/settings/security');

        $response->assertOk();
    }
}
