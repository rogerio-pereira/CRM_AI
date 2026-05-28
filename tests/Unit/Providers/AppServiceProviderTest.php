<?php

namespace Tests\Unit\Providers;

use App\Providers\AppServiceProvider;
use Illuminate\Validation\Rules\Password;
use ReflectionClass;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_password_defaults_are_strict_in_production(): void
    {
        $this->app['env'] = 'production';

        $provider = new AppServiceProvider($this->app);
        $this->invokeConfigureDefaults($provider);

        $rule = Password::default();

        $this->assertInstanceOf(Password::class, $rule);
    }

    private function invokeConfigureDefaults(AppServiceProvider $provider): void
    {
        $reflection = new ReflectionClass($provider);
        $method = $reflection->getMethod('configureDefaults');

        $method->invoke($provider);
    }
}
