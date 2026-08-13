<?php

namespace App\Ai\Contracts;

interface DiscoveryAdapter
{
    /**
     * Discover lead candidates from public/free sources (ADR-015).
     *
     * @param  array{limit?: int, instructions?: string}  $options
     * @return array{leads: list<array<string, mixed>>, skipped: list<array<string, mixed>>}
     */
    public function discover(array $options = []): array;
}
