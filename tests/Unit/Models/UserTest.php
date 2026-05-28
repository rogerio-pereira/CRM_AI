<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_initials_returns_first_letters_of_up_to_two_name_parts(): void
    {
        $user = User::factory()->make(['name' => 'Jane Doe Smith']);

        $this->assertSame('JD', $user->initials());
    }

    public function test_initials_returns_single_letter_for_one_name_part(): void
    {
        $user = User::factory()->make(['name' => 'Madonna']);

        $this->assertSame('M', $user->initials());
    }
}
