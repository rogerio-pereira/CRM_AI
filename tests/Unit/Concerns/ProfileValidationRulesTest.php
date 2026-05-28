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
        $validator = Validator::make([], $this->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_name_must_not_exceed_255_characters(): void
    {
        $validator = Validator::make([
            'name' => str_repeat('a', 256),
            'email' => 'valid@example.com',
        ], $this->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_email_must_be_a_valid_address(): void
    {
        $validator = Validator::make([
            'name' => 'Jane Doe',
            'email' => 'not-an-email',
        ], $this->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_email_must_be_unique_when_no_user_id_is_provided(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $validator = Validator::make([
            'name' => 'Jane Doe',
            'email' => 'taken@example.com',
        ], $this->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_email_unique_rule_ignores_the_current_user_when_updating(): void
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);

        $validator = Validator::make([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ], $this->rules($user->id));

        $this->assertFalse($validator->fails());
    }

    public function test_email_must_remain_unique_to_other_users_when_updating(): void
    {
        $existing = User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'jane@example.com']);

        $validator = Validator::make([
            'name' => 'Jane Doe',
            'email' => 'taken@example.com',
        ], $this->rules($user->id));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());

        $this->assertNotSame($existing->id, $user->id);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(?int $userId = null): array
    {
        return (new class
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
    }
}
