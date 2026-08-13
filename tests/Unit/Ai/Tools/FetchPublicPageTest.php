<?php

namespace Tests\Unit\Ai\Tools;

use App\Ai\Support\PublicUrlGuard;
use App\Ai\Tools\FetchPublicPage;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class FetchPublicPageTest extends TestCase
{
    public function test_rejects_disallowed_urls(): void
    {
        $tool = new FetchPublicPage(new PublicUrlGuard);

        $result = $tool->handle(new Request([
            'url' => 'http://127.0.0.1/secret',
        ]));

        $this->assertStringContainsString('Rejected', (string) $result);
    }

    public function test_fetches_and_returns_plain_text_from_public_page(): void
    {
        Http::fake([
            'https://example.com/biz' => Http::response(
                '<html><body><h1>GreenSprout Lawn Care</h1><p>Serving Lakeland</p></body></html>',
                200,
            ),
        ]);

        $tool = new FetchPublicPage(new PublicUrlGuard);

        $result = (string) $tool->handle(new Request([
            'url' => 'https://example.com/biz',
        ]));

        $this->assertStringContainsString('GreenSprout Lawn Care', $result);
        $this->assertStringContainsString('Serving Lakeland', $result);
        $this->assertStringNotContainsString('<h1>', $result);
    }

    public function test_reports_http_failures(): void
    {
        Http::fake([
            'https://example.com/missing' => Http::response('Nope', 404),
        ]);

        $tool = new FetchPublicPage(new PublicUrlGuard);

        $result = (string) $tool->handle(new Request([
            'url' => 'https://example.com/missing',
        ]));

        $this->assertStringContainsString('404', $result);
    }
}
