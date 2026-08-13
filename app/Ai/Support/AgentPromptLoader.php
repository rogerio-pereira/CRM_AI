<?php

namespace App\Ai\Support;

use Illuminate\Support\Facades\File;
use RuntimeException;

class AgentPromptLoader
{
    /**
     * Load approved agent instructions from a markdown prompt file.
     *
     * Prefer the "## System Prompt" section when present; otherwise use the full file.
     */
    public function load(string $path): string
    {
        $absolutePath = $this->resolvePath($path);

        if (! File::exists($absolutePath)) {
            throw new RuntimeException('Agent prompt file not found: '.$absolutePath);
        }

        $contents = File::get($absolutePath);

        if (! is_string($contents) || trim($contents) === '') {
            throw new RuntimeException('Agent prompt file is empty: '.$absolutePath);
        }

        $systemPrompt = $this->extractSystemPromptSection($contents);

        if ($systemPrompt !== null) {
            return $systemPrompt;
        }

        return trim($contents);
    }

    protected function resolvePath(string $path): string
    {
        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        return base_path($path);
    }

    protected function isAbsolutePath(string $path): bool
    {
        if (str_starts_with($path, '/')) {
            return true;
        }

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
            return true;
        }

        return false;
    }

    protected function extractSystemPromptSection(string $contents): ?string
    {
        if (! preg_match('/^##\s+System Prompt\s*$/m', $contents, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $start = $matches[0][1] + strlen($matches[0][0]);
        $remainder = substr($contents, $start);

        if ($remainder === false) {
            return null;
        }

        $trimmed = trim($remainder);

        if ($trimmed === '') {
            return null;
        }

        return $trimmed;
    }
}
