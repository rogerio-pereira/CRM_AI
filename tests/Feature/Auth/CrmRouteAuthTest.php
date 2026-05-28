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
            'leads stub' => ['/leads'],
            'opportunities stub' => ['/opportunities'],
            'follow-ups stub' => ['/follow-ups'],
            'tasks stub' => ['/tasks'],
        ];
    }

    public static function authenticatedCrmRoutesProvider(): array
    {
        return [
            'dashboard' => ['/dashboard'],
            'settings profile' => ['/settings/profile'],
            'leads stub' => ['/leads'],
            'opportunities stub' => ['/opportunities'],
            'follow-ups stub' => ['/follow-ups'],
            'tasks stub' => ['/tasks'],
        ];
    }

    #[DataProvider('guestProtectedCrmRoutesProvider')]
    public function test_guests_are_redirected_from_crm_routes(string $url): void
    {
        $this->get($url)->assertRedirect(route('login'));
    }

    #[DataProvider('authenticatedCrmRoutesProvider')]
    public function test_authenticated_verified_users_can_access_crm_routes(string $url): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->get($url)->assertOk();
    }

    public function test_authenticated_users_can_access_security_settings_after_password_confirmation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/settings/security')
            ->assertOk();
    }
}
