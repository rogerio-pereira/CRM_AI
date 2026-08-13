<?php

namespace App\Ai\Support;

class PublicUrlGuard
{
    /**
     * Validate that a URL is a public http(s) resource suitable for compliant fetching.
     */
    public function isAllowed(?string $url): bool
    {
        if ($url === null) {
            return false;
        }

        $trimmed = trim($url);

        if ($trimmed === '') {
            return false;
        }

        $parts = parse_url($trimmed);

        if (! is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if ($scheme !== 'http' && $scheme !== 'https') {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '') {
            return false;
        }

        if ($this->isLocalHost($host)) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            if (! $this->isPublicIp($host)) {
                return false;
            }
        }

        return true;
    }

    protected function isLocalHost(string $host): bool
    {
        if ($host === 'localhost') {
            return true;
        }

        if (str_ends_with($host, '.localhost')) {
            return true;
        }

        if (str_ends_with($host, '.local')) {
            return true;
        }

        return false;
    }

    protected function isPublicIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
            return true;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
            return true;
        }

        return false;
    }
}
