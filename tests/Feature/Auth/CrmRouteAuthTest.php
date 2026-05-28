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
    public static function protectedCrmRoutesProvider(): array
    {
        return [
            'dashboard' => ['/dashboard'],
            'settings profile' => ['/settings/profile'],
            'settings appearance' => ['/settings/appearance'],
            'settings security' => ['/settings/security'],
        ];
    }

    #[DataProvider('protectedCrmRoutesProvider')]
    public function test_guests_are_redirected_from_crm_routes(string $url): void
    {
        $this->get($url)->assertRedirect(route('login'));
    }

    #[DataProvider('protectedCrmRoutesProvider')]
    public function test_authenticated_verified_users_can_access_crm_routes(string $url): void
    {
        $user = User::factory()->create();

        $session = [];
        if ($url === '/settings/security') {
            $session['auth.password_confirmed_at'] = time();
        }

        $this->actingAs($user)
            ->withSession($session)
            ->get($url)
            ->assertOk();
    }
}
