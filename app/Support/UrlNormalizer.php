<?php

namespace App\Support;

class UrlNormalizer
{
    public static function normalize(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $trimmed = trim($url);

        if ($trimmed === '') {
            return null;
        }

        $lowered = strtolower($trimmed);
        $hasHttp = str_starts_with($lowered, 'http://');
        $hasHttps = str_starts_with($lowered, 'https://');

        if ($hasHttp || $hasHttps) {
            return $trimmed;
        }

        return 'https://'.$trimmed;
    }
}
