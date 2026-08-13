<?php

namespace App\Ai\Tools;

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

    public function description(): Stringable|string
    {
        return 'Fetch a public http(s) page and return plain text. Do not use private or paid sources.';
    }

    public function handle(Request $request): Stringable|string
    {
        $url = $request->string('url')->toString();

        if (! $this->isPublicHttpUrl($url)) {
            return 'Rejected: URL is not a public http(s) resource.';
        }

        $response = Http::timeout(self::TIMEOUT_SECONDS)
                            ->withHeaders([
                                'User-Agent' => 'FrontPorchCRM-Prospecting/1.0',
                                'Accept' => 'text/html,text/plain',
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
        $collapsed = preg_replace('/\s+/u', ' ', $text);

        if (! is_string($collapsed)) {
            $collapsed = '';
        }

        $text = trim($collapsed);

        if ($text === '') {
            return 'Fetch succeeded but no readable text was found.';
        }

        return Str::limit($text, 8_000, '…');
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema->string()->required(),
        ];
    }

    private function isPublicHttpUrl(string $url): bool
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme !== 'http' && $scheme !== 'https') {
            return false;
        }

        if ($host === '' || $host === 'localhost') {
            return false;
        }

        if (str_ends_with($host, '.local') || str_ends_with($host, '.localhost')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            return true;
        }

        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        $publicIp = filter_var($host, FILTER_VALIDATE_IP, $flags);

        return $publicIp !== false;
    }
}
