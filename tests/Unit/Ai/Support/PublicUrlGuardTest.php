<?php

namespace Tests\Unit\Ai\Support;

use App\Ai\Support\PublicUrlGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicUrlGuardTest extends TestCase
{
    private PublicUrlGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guard = new PublicUrlGuard;
    }

    #[DataProvider('allowedUrls')]
    public function test_allows_public_http_urls(string $url): void
    {
        $this->assertTrue($this->guard->isAllowed($url));
    }

    #[DataProvider('rejectedUrls')]
    public function test_rejects_non_public_urls(?string $url): void
    {
        $this->assertFalse($this->guard->isAllowed($url));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function allowedUrls(): array
    {
        return [
            'https site' => ['https://example.com/about'],
            'http site' => ['http://example.com'],
        ];
    }

    /**
     * @return array<string, array{0: ?string}>
     */
    public static function rejectedUrls(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'ftp' => ['ftp://example.com/file'],
            'localhost' => ['http://localhost/admin'],
            'local domain' => ['https://shop.local'],
            'private ipv4' => ['http://192.168.1.10/'],
            'loopback' => ['http://127.0.0.1/'],
        ];
    }
}
