<?php

namespace Tests\Unit\Support;

use App\Support\UrlNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UrlNormalizerTest extends TestCase
{
    #[DataProvider('urlProvider')]
    public function test_normalize_adds_https_when_scheme_is_missing(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, UrlNormalizer::normalize($input));
    }

    /**
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    public static function urlProvider(): array
    {
        return [
            'null' => [null, null],
            'empty' => ['', null],
            'whitespace' => ['   ', null],
            'domain only' => ['acme.com', 'https://acme.com'],
            'www' => ['www.acme.com', 'https://www.acme.com'],
            'already https' => ['https://acme.com', 'https://acme.com'],
            'already http' => ['http://acme.com', 'http://acme.com'],
            'path without scheme' => ['acme.com/about', 'https://acme.com/about'],
        ];
    }
}
