<?php

namespace Tests\Unit\Ai\Tools;

use App\Ai\Tools\FetchPublicPage;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class FetchPublicPageTest extends TestCase
{
    public function test_rejects_private_urls(): void
    {
        $tool = app(FetchPublicPage::class);

        $result = $tool->handle(new Request([
            'url' => 'http://127.0.0.1/secret',
        ]));

        $this->assertStringContainsString('Rejected', (string) $result);
    }

    public function test_fetches_plain_text_from_public_page(): void
    {
        Http::fake([
            'https://example.com/biz' => Http::response(
                '<html><body><h1>GreenSprout</h1></body></html>',
                200,
            ),
        ]);

        $tool = app(FetchPublicPage::class);

        $result = (string) $tool->handle(new Request([
            'url' => 'https://example.com/biz',
        ]));

        $this->assertStringContainsString('GreenSprout', $result);
        $this->assertStringNotContainsString('<h1>', $result);
    }
}
