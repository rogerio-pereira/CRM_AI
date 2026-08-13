<?php

namespace App\Ai\Tools;

use App\Ai\Support\PublicUrlGuard;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class FetchPublicPage implements Tool
{
    private const MAX_BYTES = 200_000;

    private const TIMEOUT_SECONDS = 10;

    public function __construct(
        private readonly PublicUrlGuard $urlGuard,
    ) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Fetch a public web page over HTTP(S) and return plain text for prospecting research. Only public, free pages. Do not use private, credentialed, or paid data APIs.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $url = $request->string('url')->toString();

        if (! $this->urlGuard->isAllowed($url)) {
            return 'Rejected: URL is not an allowed public http(s) resource.';
        }

        $response = Http::timeout(self::TIMEOUT_SECONDS)
            ->withHeaders([
                'User-Agent' => 'FrontPorchCRM-Prospecting/1.0 (+https://frontporchcreative.io)',
                'Accept' => 'text/html,application/xhtml+xml,text/plain;q=0.9,*/*;q=0.8',
            ])
            ->withOptions([
                'allow_redirects' => [
                    'max' => 3,
                ],
            ])
            ->get($url);

        if (! $response->successful()) {
            return 'Fetch failed with HTTP status '.$response->status().'.';
        }

        $body = $response->body();

        if (strlen($body) > self::MAX_BYTES) {
            $body = substr($body, 0, self::MAX_BYTES);
        }

        $text = html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';
        $text = trim($text);

        if ($text === '') {
            return 'Fetch succeeded but no readable text was found.';
        }

        return Str::limit($text, 8_000, '…');
    }

    /**
     * Get the tool's schema definition.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema->string()->required(),
        ];
    }
}
