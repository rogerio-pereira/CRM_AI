<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\DeleteUserModal;
use App\Livewire\Settings\Profile;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        $response = $this->get(route('profile.edit'));

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        $response = Livewire::test(Profile::class)
                        ->set('name', 'Test User')
                        ->set('email', 'test@example.com')
                        ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $user->refresh();

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_email_address_is_unchanged(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        $response = Livewire::test(Profile::class)
                        ->set('name', 'Test User')
                        ->set('email', $user->email)
                        ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $user->refresh();

        $this->assertNotNull($user->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        $response = Livewire::test(DeleteUserModal::class)
                        ->set('password', 'password')
                        ->call('deleteUser');

        $response->assertHasNoErrors()
            ->assertRedirect('/');

        $deletedUser = $user->fresh();

        $this->assertNull($deletedUser);

        $isAuthenticated = auth()->check();

        $this->assertFalse($isAuthenticated);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        $response = Livewire::test(DeleteUserModal::class)
                        ->set('password', 'wrong-password')
                        ->call('deleteUser');

        $response->assertHasErrors(['password']);

        $persistedUser = $user->fresh();

        $this->assertNotNull($persistedUser);
    }

    public function test_verification_notification_can_be_resent(): void
    {
        $this->skipUnlessFortifyHas(Features::emailVerification());

        Notification::fake();

        $user = User::factory()
                    ->unverified()
                    ->create();

        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->call('resendVerificationNotification');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_resend_verification_redirects_verified_user(): void
    {
        $this->skipUnlessFortifyHas(Features::emailVerification());

        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->call('resendVerificationNotification')
            ->assertRedirect(route('dashboard', absolute: false));
    }
}
