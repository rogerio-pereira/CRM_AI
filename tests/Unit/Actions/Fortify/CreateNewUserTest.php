<?php

namespace Tests\Unit\Actions\Fortify;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateNewUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_user_with_valid_input(): void
    {
        $action = app(CreateNewUser::class);

        $user = $action->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
    }

    public function test_it_rejects_duplicate_email_addresses(): void
    {
        User::factory()
                ->create(['email' => 'taken@example.com']);

        $action = app(CreateNewUser::class);

        $this->expectException(ValidationException::class);

        $action->create([
            'name' => 'Jane Doe',
            'email' => 'taken@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
    }

    public function test_it_rejects_invalid_profile_and_password_input(): void
    {
        $action = app(CreateNewUser::class);

        $this->expectException(ValidationException::class);

        $action->create([
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]);
    }
}
