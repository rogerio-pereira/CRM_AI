<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use RuntimeException;

#[MaxSteps(2)]
#[Timeout(60)]
class WriteFirstContactEmailAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    private const APPROVED_PROMPT_PATH = 'docs/prompts/laravel_tools/write-first-contact-email.md';

    public function instructions(): string
    {
        $path = base_path(self::APPROVED_PROMPT_PATH);

        if (! File::exists($path)) {
            throw new RuntimeException('First contact email prompt file not found: '.$path);
        }

        $contents = File::get($path);
        $prompt = trim((string) $contents);

        if ($prompt === '') {
            throw new RuntimeException('First contact email prompt file is empty: '.$path);
        }

        return $prompt;
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
                'channel' => $schema->string()
                                    ->required(),
                'subject' => $schema->string()
                                    ->required(),
                'body' => $schema->string()
                                ->required(),
            ];
    }
}
