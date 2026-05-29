<?php

namespace App\Ai\Contracts;

interface AiAgent
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function handle(array $context): array;
}
