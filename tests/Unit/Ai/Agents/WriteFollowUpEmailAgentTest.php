<?php

namespace Tests\Unit\Ai\Agents;

use App\Ai\Agents\WriteFollowUpEmailAgent;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Providers\Tools\WebFetch;
use RuntimeException;
use Tests\TestCase;

class WriteFollowUpEmailAgentTest extends TestCase
{
    public function test_instructions_load_the_approved_follow_up_prompt(): void
    {
        $agent = new WriteFollowUpEmailAgent;
        $instructions = $agent->instructions();

        $this->assertStringContainsString('Gustavo Ferreira', $instructions);
        $this->assertStringContainsString('Allison Hardy', $instructions);
        $this->assertStringContainsString('sequence_step', $instructions);
        $this->assertStringContainsString('New insight + new quick win', $instructions);
        $this->assertStringContainsString('this is the last email', $instructions);
        $this->assertStringContainsString('Do not use `Re:` in the subject', $instructions);
        $this->assertStringContainsString('previous_emails', $instructions);
        $this->assertStringContainsString('3 dates and times', $instructions);
        $this->assertStringContainsString('1-hour online discovery', $instructions);
        $this->assertStringContainsString('[Front Porch Creative](https://frontporchcreative.io)', $instructions);
        $this->assertStringContainsString('When `client.website` is present, fetch that page', $instructions);
        $this->assertStringContainsString('Step `1` must **not** say this is the last email', $instructions);
    }

    public function test_agent_exposes_web_fetch_tool(): void
    {
        $agent = new WriteFollowUpEmailAgent;
        $toolsIterator = $agent->tools();
        $tools = iterator_to_array($toolsIterator);
        $webFetch = $tools[0] ?? null;

        $this->assertCount(1, $tools);
        $this->assertInstanceOf(WebFetch::class, $webFetch);
    }

    public function test_schema_requires_channel_subject_and_body(): void
    {
        $agent = new WriteFollowUpEmailAgent;
        $schemaFactory = new JsonSchemaTypeFactory;
        $schema = $agent->schema($schemaFactory);

        $this->assertArrayHasKey('channel', $schema);
        $this->assertArrayHasKey('subject', $schema);
        $this->assertArrayHasKey('body', $schema);
    }

    public function test_agent_throws_when_prompt_file_is_missing(): void
    {
        $path = base_path('docs/prompts/laravel_tools/write-follow-up-email.md');
        $file = File::partialMock();
        $file->shouldReceive('exists')
            ->with($path)
            ->andReturn(false);

        $agent = new WriteFollowUpEmailAgent;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Follow-up email prompt file not found');

        $agent->instructions();
    }

    public function test_agent_throws_when_prompt_file_is_empty(): void
    {
        $path = base_path('docs/prompts/laravel_tools/write-follow-up-email.md');
        $file = File::partialMock();
        $file->shouldReceive('exists')
            ->with($path)
            ->andReturn(true);
        $file->shouldReceive('get')
            ->with($path)
            ->andReturn('   ');

        $agent = new WriteFollowUpEmailAgent;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Follow-up email prompt file is empty');

        $agent->instructions();
    }
}
