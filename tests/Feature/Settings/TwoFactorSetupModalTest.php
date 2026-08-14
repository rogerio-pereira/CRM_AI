<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\TwoFactorSetupModal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorSetupModalTest extends TestCase
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
    }

    public function test_two_factor_setup_can_be_started(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        Livewire::test(TwoFactorSetupModal::class, ['requiresConfirmation' => true])
            ->call('startTwoFactorSetup')
            ->assertHasNoErrors()
            ->assertSet('manualSetupKey', fn (string $key) => $key !== '')
            ->assertSet('qrCodeSvg', fn (string $svg) => $svg !== '');
    }

    public function test_two_factor_setup_reports_error_when_setup_data_is_unavailable(): void
    {
        $user = User::factory()
                    ->create();

        $corruptedAttributes = [
                'two_factor_secret' => 'invalid-encrypted-data',
            ];

        $user->forceFill($corruptedAttributes)
            ->save();

        $this->actingAs($user);

        Livewire::test(TwoFactorSetupModal::class, ['requiresConfirmation' => true])
            ->call('startTwoFactorSetup')
            ->assertHasErrors(['setupData']);
    }

    public function test_two_factor_setup_reports_error_when_secret_is_missing(): void
    {
        $user = User::factory()
                    ->create([
                        'two_factor_secret' => null,
                    ]);

        $this->actingAs($user);

        $this->mock(EnableTwoFactorAuthentication::class, function ($mock): void {
            $mock->shouldReceive('__invoke')
                ->once();
        });

        Livewire::test(TwoFactorSetupModal::class, ['requiresConfirmation' => true])
            ->call('startTwoFactorSetup')
            ->assertHasErrors(['setupData']);
    }

    public function test_verification_step_is_shown_when_confirmation_is_required(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        Livewire::test(TwoFactorSetupModal::class, ['requiresConfirmation' => true])
            ->call('showVerificationIfNecessary')
            ->assertSet('showVerificationStep', true)
            ->assertHasNoErrors();
    }

    public function test_two_factor_is_enabled_without_confirmation_step_when_not_required(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        Livewire::test(TwoFactorSetupModal::class, ['requiresConfirmation' => false])
            ->call('showVerificationIfNecessary')
            ->assertSet('showVerificationStep', false)
            ->assertDispatched('two-factor-enabled');
    }

    public function test_two_factor_can_be_confirmed_with_valid_code(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()
                    ->create();

        $recoveryCodes = ['recovery-code-1'];
        $encodedRecoveryCodes = json_encode($recoveryCodes);
        $encryptedSecret = encrypt($secret);
        $encryptedRecoveryCodes = encrypt($encodedRecoveryCodes);
        $unconfirmedTwoFactorAttributes = [
                        'two_factor_secret' => $encryptedSecret,
                        'two_factor_recovery_codes' => $encryptedRecoveryCodes,
                        'two_factor_confirmed_at' => null,
            ];

        $user->forceFill($unconfirmedTwoFactorAttributes)
            ->save();

        $this->actingAs($user);

        $currentOtp = $google2fa->getCurrentOtp($secret);

        Livewire::test(TwoFactorSetupModal::class, ['requiresConfirmation' => true])
            ->set('code', $currentOtp)
            ->call('confirmTwoFactor')
            ->assertHasNoErrors()
            ->assertDispatched('two-factor-enabled');

        $confirmedUser = $user->fresh();

        $this->assertNotNull($confirmedUser->two_factor_confirmed_at);
    }

    public function test_two_factor_confirmation_requires_valid_code(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()
                    ->create();

        $recoveryCodes = ['recovery-code-1'];
        $encodedRecoveryCodes = json_encode($recoveryCodes);
        $encryptedSecret = encrypt($secret);
        $encryptedRecoveryCodes = encrypt($encodedRecoveryCodes);
        $unconfirmedTwoFactorAttributes = [
                        'two_factor_secret' => $encryptedSecret,
                        'two_factor_recovery_codes' => $encryptedRecoveryCodes,
                        'two_factor_confirmed_at' => null,
            ];

        $user->forceFill($unconfirmedTwoFactorAttributes)
            ->save();

        $this->actingAs($user);

        Livewire::test(TwoFactorSetupModal::class, ['requiresConfirmation' => true])
            ->set('code', '000000')
            ->call('confirmTwoFactor')
            ->assertHasErrors(['code']);
    }

    public function test_verification_state_can_be_reset(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        Livewire::test(TwoFactorSetupModal::class, ['requiresConfirmation' => true])
            ->set('showVerificationStep', true)
            ->set('code', '123456')
            ->call('resetVerification')
            ->assertSet('showVerificationStep', false)
            ->assertSet('code', '')
            ->assertHasNoErrors();
    }

    public function test_modal_can_be_closed_and_reset(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        Livewire::test(TwoFactorSetupModal::class, ['requiresConfirmation' => true])
            ->set('code', '123456')
            ->set('showVerificationStep', true)
            ->set('setupComplete', true)
            ->call('closeModal')
            ->assertSet('code', '')
            ->assertSet('showVerificationStep', false)
            ->assertSet('setupComplete', false)
            ->assertHasNoErrors();
    }

    public function test_modal_config_reflects_current_step(): void
    {
        $user = User::factory()
                    ->create();

        $this->actingAs($user);

        $component = Livewire::test(TwoFactorSetupModal::class, ['requiresConfirmation' => true]);

        $setupInstance = $component->instance();
        $setupTitle = $setupInstance->modalConfig['title'];

        $this->assertSame(__('Enable two-factor authentication'), $setupTitle);

        $component->set('showVerificationStep', true);

        $verificationInstance = $component->instance();
        $verificationTitle = $verificationInstance->modalConfig['title'];

        $this->assertSame(__('Verify authentication code'), $verificationTitle);

        $component->set('setupComplete', true);

        $completedInstance = $component->instance();
        $completedTitle = $completedInstance->modalConfig['title'];

        $this->assertSame(__('Two-factor authentication enabled'), $completedTitle);
    }
}
