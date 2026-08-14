<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\TwoFactor\RecoveryCodes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use Tests\TestCase;

class RecoveryCodesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());
    }

    public function test_recovery_codes_are_loaded_when_two_factor_is_enabled(): void
    {
        $user = User::factory()
                    ->withTwoFactor()
                    ->create();

        $this->actingAs($user);

        Livewire::test(RecoveryCodes::class)
            ->assertSet('recoveryCodes', ['recovery-code-1']);
    }

    public function test_recovery_codes_can_be_regenerated(): void
    {
        $user = User::factory()
                    ->withTwoFactor()
                    ->create();

        $this->actingAs($user);

        Livewire::test(RecoveryCodes::class)
            ->call('regenerateRecoveryCodes')
            ->assertHasNoErrors()
            ->assertSet('recoveryCodes', fn (array $codes) => count($codes) > 0);
    }

    public function test_recovery_codes_report_error_when_data_cannot_be_decrypted(): void
    {
        $user = User::factory()
                    ->withTwoFactor()
                    ->create();

        $corruptedAttributes = [
                'two_factor_recovery_codes' => 'invalid-encrypted-data',
            ];

        $user->forceFill($corruptedAttributes)
            ->save();

        $this->actingAs($user);

        Livewire::test(RecoveryCodes::class)
            ->assertHasErrors(['recoveryCodes'])
            ->assertSet('recoveryCodes', []);
    }
}
