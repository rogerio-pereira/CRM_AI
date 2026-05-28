<?php

namespace Tests\Unit\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class FortifyServiceProviderTest extends TestCase
{
    public function test_two_factor_rate_limiter_uses_login_id_from_session(): void
    {
        $request = Request::create('/two-factor-challenge', 'POST');
        $session = $this->app['session.store'];
        $session->put('login.id', 42);
        $request->setLaravelSession($session);

        $limiter = RateLimiter::limiter('two-factor');

        $this->assertNotNull($limiter);

        $limit = $limiter($request);

        $this->assertInstanceOf(Limit::class, $limit);
    }

    public function test_passkeys_rate_limiter_uses_credential_id_when_provided(): void
    {
        $request = Request::create('/passkeys', 'POST', [
            'credential' => ['id' => 'credential-uuid'],
        ]);
        $request->server->set('REMOTE_ADDR', '10.0.0.1');

        $limiter = RateLimiter::limiter('passkeys');

        $this->assertNotNull($limiter);

        $limit = $limiter($request);

        $this->assertInstanceOf(Limit::class, $limit);
    }

    public function test_passkeys_rate_limiter_falls_back_to_session_id_when_credential_id_is_missing(): void
    {
        $request = Request::create('/passkeys', 'POST');
        $request->setLaravelSession($this->app['session.store']);
        $request->server->set('REMOTE_ADDR', '10.0.0.1');

        $limiter = RateLimiter::limiter('passkeys');

        $this->assertNotNull($limiter);

        $limit = $limiter($request);

        $this->assertInstanceOf(Limit::class, $limit);
    }
}
