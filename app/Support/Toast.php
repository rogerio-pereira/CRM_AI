<?php

namespace App\Support;

use Flux\Flux;

class Toast
{
    public static function show(string $variant, string $text, int $duration = 5000): void
    {
        Flux::toast(
            variant: $variant,
            text: $text,
            duration: $duration,
        );
    }
}
