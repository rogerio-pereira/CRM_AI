<?php

namespace App\Ai\Contracts;

interface DiscoveryAdapter
{
    /**
     * Discover lead candidates from public/free sources (ADR-015).
     *
     * @param  array{
     *     limit?: int,
     *     allow_incomplete?: bool,
     *     region_priority?: list<string>,
     *     instructions?: string,
     * }  $options
     * @return array{
     *     schema_version: int,
     *     agent: string,
     *     target_count: int,
     *     region_priority: list<string>,
     *     leads: list<array<string, mixed>>,
     *     skipped: list<array<string, mixed>>,
     * }
     */
    public function discover(array $options = []): array;
}
