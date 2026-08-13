<?php

namespace Tests\Unit\Ai\Tools;

use App\Ai\Tools\FetchPublicPage;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class FetchPublicPageTest extends TestCase
{
    public function test_rejects_private_urls(): void
    {
        $tool = app(FetchPublicPage::class);

        $request = new Request([
            'url' => 'http://127.0.0.1/secret',
        ]);

        $result = $tool->handle($request);
        $resultText = (string) $result;

        $this->assertStringContainsString('Rejected', $resultText);
    }

    public function test_rejects_localhost_local_hosts_and_non_http_schemes(): void
    {
        $tool = app(FetchPublicPage::class);

        $malformed = $this->fetchText($tool, 'http://#');
        $ftp = $this->fetchText($tool, 'ftp://example.com/page');
        $localhost = $this->fetchText($tool, 'http://localhost/secret');
        $dotLocal = $this->fetchText($tool, 'http://intranet.local/page');
        $dotLocalhost = $this->fetchText($tool, 'http://app.localhost/page');

        $this->assertStringContainsString('Rejected', $malformed);
        $this->assertStringContainsString('Rejected', $ftp);
        $this->assertStringContainsString('Rejected', $localhost);
        $this->assertStringContainsString('Rejected', $dotLocal);
        $this->assertStringContainsString('Rejected', $dotLocalhost);
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

        $request = new Request([
            'url' => 'https://example.com/biz',
        ]);

        $result = $tool->handle($request);
        $resultText = (string) $result;

        $this->assertStringContainsString('GreenSprout', $resultText);
        $this->assertStringNotContainsString('<h1>', $resultText);
    }

    public function test_fetches_public_ip_and_reports_http_errors_empty_and_truncated_bodies(): void
    {
        $oversizedBody = str_repeat('a', 200_001);

        Http::fake([
            'http://8.8.8.8/lookup' => Http::response(
                '<p>Public DNS</p>',
                200,
            ),
            'https://example.com/fail' => Http::response(
                'error',
                503,
            ),
            'https://example.com/empty' => Http::response(
                '<html><body>   </body></html>',
                200,
            ),
            'https://example.com/huge' => Http::response(
                $oversizedBody,
                200,
            ),
        ]);

        $tool = app(FetchPublicPage::class);

        $publicIpText = $this->fetchText($tool, 'http://8.8.8.8/lookup');
        $failedText = $this->fetchText($tool, 'https://example.com/fail');
        $emptyText = $this->fetchText($tool, 'https://example.com/empty');
        $truncatedText = $this->fetchText($tool, 'https://example.com/huge');

        $this->assertStringContainsString('Public DNS', $publicIpText);
        $this->assertStringContainsString('Fetch failed with HTTP status 503', $failedText);
        $this->assertStringContainsString('no readable text', $emptyText);
        $this->assertStringContainsString('a', $truncatedText);
        $this->assertLessThan(200_001, strlen($truncatedText));
    }

    public function test_exposes_description_and_url_schema(): void
    {
        $tool = app(FetchPublicPage::class);
        $schemaFactory = new JsonSchemaTypeFactory;
        $schema = $tool->schema($schemaFactory);
        $description = $tool->description();
        $descriptionText = (string) $description;

        $this->assertArrayHasKey('url', $schema);
        $this->assertStringContainsString('public http(s) page', $descriptionText);
    }

    private function fetchText(FetchPublicPage $tool, string $url): string
    {
        $request = new Request([
            'url' => $url,
        ]);

        $result = $tool->handle($request);

        return (string) $result;
    }
}
