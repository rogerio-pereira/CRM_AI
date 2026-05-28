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

        if (preg_match('#^https?://#i', $trimmed) === 1) {
            return $trimmed;
        }

        return 'https://'.$trimmed;
    }
}
