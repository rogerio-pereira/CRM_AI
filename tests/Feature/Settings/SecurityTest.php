<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Security;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        Features::passkeys([
            'confirmPassword' => true,
        ]);
    }

    public function test_security_settings_page_can_be_rendered(): void
    {
        $user = User::factory()
                    ->create();

        $response = $this->actingAs($user)
                        ->withSession(['auth.password_confirmed_at' => time()])
                        ->get(route('security.edit'));

        $response->assertOk();

        $response->assertSee('Passkeys');
        $response->assertSee('No passkeys yet');
        $response->assertSee('Two-factor authentication');
        $response->assertSee('Enable 2FA');
    }

    public function test_security_settings_page_requires_password_confirmation_when_enabled(): void
    {
        $user = User::factory()
                    ->create();

        $response = $this->actingAs($user)
                        ->get(route('security.edit'));

        $response->assertRedirect(route('password.confirm'));
    }

    public function test_security_settings_page_renders_without_two_factor_when_feature_is_disabled(): void
    {
        config(['fortify.features' => []]);

        $user = User::factory()
                    ->create();

        $response = $this->actingAs($user)
                        ->withSession(['auth.password_confirmed_at' => time()])
                        ->get(route('security.edit'));

        $response->assertOk()
            ->assertSee('Update password')
            ->assertDontSee('Manage your passkeys for passwordless sign-in')
            ->assertDontSee('Add a passkey to sign in without a password')
            ->assertDontSee('Two-factor authentication');
    }

    public function test_two_factor_authentication_disabled_when_confirmation_abandoned_between_requests(): void
    {
        $user = User::factory()
                    ->create();

        $recoveryCodes = ['code1', 'code2'];
        $encodedRecoveryCodes = json_encode($recoveryCodes);
        $encryptedSecret = encrypt('test-secret');
        $encryptedRecoveryCodes = encrypt($encodedRecoveryCodes);
        $unconfirmedTwoFactorAttributes = [
                'two_factor_secret' => $encryptedSecret,
                'two_factor_recovery_codes' => $encryptedRecoveryCodes,
                'two_factor_confirmed_at' => null,
            ];

        $user->forceFill($unconfirmedTwoFactorAttributes)
            ->save();

        $this->actingAs($user);

        $component = Livewire::test(Security::class);

        $component->assertSet('twoFactorEnabled', false);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);
    }

    public function test_password_can_be_updated(): void
    {
        $hashedPassword = Hash::make('password');

        $user = User::factory()
                    ->create([
                        'password' => $hashedPassword,
                    ]);

        $this->actingAs($user);

        $response = Livewire::test(Security::class)
                        ->set('current_password', 'password')
                        ->set('password', 'new-password')
                        ->set('password_confirmation', 'new-password')
                        ->call('updatePassword');

        $response->assertHasNoErrors();

        $user->refresh();

        $passwordWasUpdated = Hash::check('new-password', $user->password);

        $this->assertTrue($passwordWasUpdated);
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $hashedPassword = Hash::make('password');

        $user = User::factory()
                    ->create([
                        'password' => $hashedPassword,
                    ]);

        $this->actingAs($user);

        $response = Livewire::test(Security::class)
                        ->set('current_password', 'wrong-password')
                        ->set('password', 'new-password')
                        ->set('password_confirmation', 'new-password')
                        ->call('updatePassword');

        $response->assertHasErrors(['current_password']);
    }

    public function test_passkey_can_be_deleted(): void
    {
        $user = User::factory()
                    ->create();

        $passkey = $user->passkeys()
                        ->create([
                            'name' => 'Test Device',
                            'credential_id' => 'test-credential-id-1',
                            'credential' => ['aaguid' => '00000000-0000-0000-0000-000000000000'],
                            'last_used_at' => Carbon::now(),
                        ]);

        $this->actingAs($user);

        Livewire::test(Security::class)
            ->call('confirmDelete', $passkey->id)
            ->assertSet('showDeleteModal', true)
            ->assertSet('deletingPasskeyId', $passkey->id)
            ->call('deletePasskey')
            ->assertSet('showDeleteModal', false);

        $this->assertDatabaseMissing('passkeys', ['id' => $passkey->id]);
    }

    public function test_close_delete_modal_resets_state(): void
    {
        $user = User::factory()
                    ->create();

        $passkey = $user->passkeys()
                        ->create([
                            'name' => 'Test Device',
                            'credential_id' => 'test-credential-id-2',
                            'credential' => ['aaguid' => '00000000-0000-0000-0000-000000000000'],
                        ]);

        $this->actingAs($user);

        Livewire::test(Security::class)
            ->call('confirmDelete', $passkey->id)
            ->call('closeDeleteModal')
            ->assertSet('showDeleteModal', false)
            ->assertSet('deletingPasskeyId', null);
    }

    public function test_delete_passkey_without_selection_does_nothing(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        Livewire::test(Security::class)
            ->call('deletePasskey')
            ->assertHasNoErrors();
    }

    public function test_two_factor_can_be_disabled(): void
    {
        $user = User::factory()
                    ->withTwoFactor()
                    ->create();

        $this->actingAs($user);

        Livewire::test(Security::class)
            ->assertSet('twoFactorEnabled', true)
            ->call('disable')
            ->assertSet('twoFactorEnabled', false);
    }

    public function test_two_factor_enabled_event_updates_state(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        Livewire::test(Security::class)
            ->assertSet('twoFactorEnabled', false)
            ->call('onTwoFactorEnabled')
            ->assertSet('twoFactorEnabled', true);
    }
}
