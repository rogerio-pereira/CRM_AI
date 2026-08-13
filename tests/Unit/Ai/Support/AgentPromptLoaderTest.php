<?php

namespace Tests\Unit\Ai\Support;

use App\Ai\Support\AgentPromptLoader;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Tests\TestCase;

class AgentPromptLoaderTest extends TestCase
{
    public function test_loads_system_prompt_section_from_markdown(): void
    {
        $path = storage_path('framework/testing/prompt-loader-test.md');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, "# Title\n\n## Purpose\n\nIgnore me.\n\n## System Prompt\n\nYou are the agent.\n\n## More\n\nKeep this too.\n");

        $loader = new AgentPromptLoader;

        $prompt = $loader->load($path);

        $this->assertStringContainsString('You are the agent.', $prompt);
        $this->assertStringContainsString('## More', $prompt);
        $this->assertStringNotContainsString('## Purpose', $prompt);

        File::delete($path);
    }

    public function test_loads_approved_prospecting_prompt_from_config_path(): void
    {
        $loader = new AgentPromptLoader;

        $prompt = $loader->load((string) config('prospecting.prompt_path'));

        $this->assertStringContainsString('Front Porch Creative', $prompt);
        $this->assertStringContainsString('public and free sources', $prompt);
    }

    public function test_throws_when_file_missing(): void
    {
        $loader = new AgentPromptLoader;

        $this->expectException(RuntimeException::class);

        $loader->load('docs/prompts/does-not-exist.md');
    }
}
