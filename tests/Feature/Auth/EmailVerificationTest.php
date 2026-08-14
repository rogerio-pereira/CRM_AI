<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::emailVerification());
    }

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()
                    ->unverified()
                    ->create();

        $response = $this->actingAs($user)
                        ->get(route('verification.notice'));

        $response->assertOk();
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()
                    ->unverified()
                    ->create();

        Event::fake();

        $expiresAt = now()
                            ->addMinutes(60);
        $emailHash = sha1($user->email);
        $verificationParameters = [
                'id' => $user->id,
                'hash' => $emailHash,
            ];

        $verificationUrl = URL::temporarySignedRoute('verification.verify', $expiresAt, $verificationParameters);

        $response = $this->actingAs($user)
                        ->get($verificationUrl);

        Event::assertDispatched(Verified::class);

        $verifiedUser = $user->fresh();

        $this->assertTrue($verifiedUser->hasVerifiedEmail());

        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()
                    ->unverified()
                    ->create();

        $expiresAt = now()
                            ->addMinutes(60);
        $invalidEmailHash = sha1('wrong-email');
        $verificationParameters = [
                'id' => $user->id,
                'hash' => $invalidEmailHash,
            ];

        $verificationUrl = URL::temporarySignedRoute('verification.verify', $expiresAt, $verificationParameters);

        $this->actingAs($user)
            ->get($verificationUrl);

        $unverifiedUser = $user->fresh();

        $this->assertFalse($unverifiedUser->hasVerifiedEmail());
    }

    public function test_already_verified_user_visiting_verification_link_is_redirected_without_firing_event_again(): void
    {
        $user = User::factory()
                    ->create([
                        'email_verified_at' => now(),
                    ]);

        Event::fake();

        $expiresAt = now()
                            ->addMinutes(60);
        $emailHash = sha1($user->email);
        $verificationParameters = [
                        'id' => $user->id,
                        'hash' => $emailHash,
            ];

        $verificationUrl = URL::temporarySignedRoute('verification.verify', $expiresAt, $verificationParameters);

        $response = $this->actingAs($user)
                        ->get($verificationUrl);

        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        $verifiedUser = $user->fresh();

        $this->assertTrue($verifiedUser->hasVerifiedEmail());

        Event::assertNotDispatched(Verified::class);
    }
}
