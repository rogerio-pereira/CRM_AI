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
        $urlInput = $request->string('url');
        $url = $urlInput->toString();

        if (! $this->isPublicHttpUrl($url)) {
            return 'Rejected: URL is not a public http(s) resource.';
        }

        $headers = [
                'User-Agent' => 'FrontPorchCRM-Prospecting/1.0',
                'Accept' => 'text/html,text/plain',
            ];

        $response = Http::timeout(self::TIMEOUT_SECONDS)
                        ->withHeaders($headers)
                        ->get($url);

        if (! $response->successful()) {
            $status = $response->status();

            return 'Fetch failed with HTTP status '.$status.'.';
        }

        $body = $response->body();

        if (strlen($body) > self::MAX_BYTES) {
            $body = substr($body, 0, self::MAX_BYTES);
        }

        $strippedBody = strip_tags($body);
        $decodedText = html_entity_decode($strippedBody, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim($decodedText);

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
        $url = $schema->string()
                    ->required();

        return [
                'url' => $url,
            ];
    }

    private function isPublicHttpUrl(string $url): bool
    {
        $trimmedUrl = trim($url);
        $parts = parse_url($trimmedUrl);

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

        $isIpAddress = filter_var($host, FILTER_VALIDATE_IP) !== false;

        if ($isIpAddress === false) {
            return true;
        }

        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        $publicIp = filter_var($host, FILTER_VALIDATE_IP, $flags);

        return $publicIp !== false;
    }
}
