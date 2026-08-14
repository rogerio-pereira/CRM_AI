<?php

namespace Tests\Unit\Concerns;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProfileValidationRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_rules_require_name_and_email(): void
    {
        $rules = $this->rules();

        $validator = Validator::make([], $rules);

        $this->assertTrue($validator->fails());

        $errors = $validator->errors()
                            ->toArray();

        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
    }

    public function test_name_must_not_exceed_255_characters(): void
    {
        $rules = $this->rules();

        $validator = Validator::make([
            'name' => str_repeat('a', 256),
            'email' => 'valid@example.com',
        ], $rules);

        $this->assertTrue($validator->fails());

        $errors = $validator->errors()
                            ->toArray();

        $this->assertArrayHasKey('name', $errors);
    }

    public function test_email_must_be_a_valid_address(): void
    {
        $rules = $this->rules();

        $validator = Validator::make([
            'name' => 'Jane Doe',
            'email' => 'not-an-email',
        ], $rules);

        $this->assertTrue($validator->fails());

        $errors = $validator->errors()
                            ->toArray();

        $this->assertArrayHasKey('email', $errors);
    }

    public function test_email_must_be_unique_when_no_user_id_is_provided(): void
    {
        User::factory()
                ->create(['email' => 'taken@example.com']);
        $rules = $this->rules();

        $validator = Validator::make([
            'name' => 'Jane Doe',
            'email' => 'taken@example.com',
        ], $rules);

        $this->assertTrue($validator->fails());

        $errors = $validator->errors()
                            ->toArray();

        $this->assertArrayHasKey('email', $errors);
    }

    public function test_email_unique_rule_ignores_the_current_user_when_updating(): void
    {
        $user = User::factory()
                    ->create(['email' => 'jane@example.com']);
        $rules = $this->rules($user->id);

        $validator = Validator::make([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ], $rules);

        $this->assertFalse($validator->fails());
    }

    public function test_email_must_remain_unique_to_other_users_when_updating(): void
    {
        $existing = User::factory()
                        ->create(['email' => 'taken@example.com']);

        $user = User::factory()
                    ->create(['email' => 'jane@example.com']);
        $rules = $this->rules($user->id);

        $validator = Validator::make([
            'name' => 'Jane Doe',
            'email' => 'taken@example.com',
        ], $rules);

        $this->assertTrue($validator->fails());

        $errors = $validator->errors()
                            ->toArray();

        $this->assertArrayHasKey('email', $errors);

        $this->assertNotSame($existing->id, $user->id);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(?int $userId = null): array
    {
        $rules = (new class
        {
            use ProfileValidationRules;

            /**
             * @return array<string, array<int, mixed>>
             */
            public function forUser(?int $userId = null): array
            {
                return $this->profileRules($userId);
            }
        })->forUser($userId);

        return $rules;
    }
}
