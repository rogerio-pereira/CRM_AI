<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationDisabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_routes_are_unavailable_when_registration_is_disabled(): void
    {
        putenv('REGISTRATION_ENABLED=false');
        $_ENV['REGISTRATION_ENABLED'] = 'false';
        $_SERVER['REGISTRATION_ENABLED'] = 'false';

        $this->refreshApplication();

        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();
    }

    protected function tearDown(): void
    {
        putenv('REGISTRATION_ENABLED=true');
        $_ENV['REGISTRATION_ENABLED'] = 'true';
        $_SERVER['REGISTRATION_ENABLED'] = 'true';

        parent::tearDown();
    }
}
