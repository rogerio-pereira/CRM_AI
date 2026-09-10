<?php

namespace Tests\Unit\Ai\Agents;

use App\Ai\Agents\WriteFirstContactEmailAgent;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Providers\Tools\WebFetch;
use RuntimeException;
use Tests\TestCase;

class WriteFirstContactEmailAgentTest extends TestCase
{
    public function test_instructions_load_the_approved_copywriting_prompt(): void
    {
        $agent = new WriteFirstContactEmailAgent;
        $instructions = $agent->instructions();

        $this->assertStringContainsString('Gustavo Ferreira', $instructions);
        $this->assertStringContainsString('Allison Hardy', $instructions);
        $this->assertStringContainsString('line of business', $instructions);
        $this->assertStringContainsString('This email **must sell**', $instructions);
        $this->assertStringContainsString('quick win', $instructions);
        $this->assertStringContainsString('must **not** replace Front Porch', $instructions);
        $this->assertStringContainsString('Avoid flattery', $instructions);
        $this->assertStringContainsString('clear CTA', $instructions);
        $this->assertStringContainsString('Sound natural and conversational', $instructions);
        $this->assertStringContainsString('Name the action: reply with **3 dates and times** they can meet', $instructions);
        $this->assertStringContainsString('online **discovery meeting** of about 1 hour', $instructions);
        $this->assertStringContainsString('Never write the word “CTA” in the email', $instructions);
        $this->assertStringContainsString('Simple English', $instructions);
        $this->assertStringContainsString('The emoji must **belong to this email**', $instructions);
        $this->assertStringContainsString('Do not copy a sample', $instructions);
        $this->assertStringContainsString('Do not sound like someone selling a car, insurance, or solar panels', $instructions);
        $this->assertStringContainsString('same** problem', $instructions);
        $this->assertStringContainsString('Do not bait with one topic and switch', $instructions);
        $this->assertStringContainsString('JSON dossier', $instructions);
        $this->assertStringContainsString('ai_recommendations', $instructions);
        $this->assertStringContainsString('opportunity_notes', $instructions);
        $this->assertStringContainsString('Never reuse a previous email', $instructions);
        $this->assertStringContainsString('does not include `contact_example`', $instructions);
        $this->assertStringContainsString('A branded or custom-domain email raises prices', $instructions);
        $this->assertStringContainsString('When `client.website` is present, fetch that page', $instructions);
        $this->assertStringContainsString('If the dossier claims a defect and the live page shows the opposite, trust the page', $instructions);
        $this->assertStringContainsString('add a clickable phone number', $instructions);
        $this->assertStringContainsString('One observation. One thread.', $instructions);
        $this->assertStringContainsString('[Front Porch Creative](https://frontporchcreative.io)', $instructions);
        $this->assertStringNotContainsString('linkedin.com/in/rogerio-pereira', $instructions);
        $this->assertStringNotContainsString('[frontporchcreative.io](https://frontporchcreative.io)', $instructions);
        $this->assertStringNotContainsString('Allowed: 👋 👀 💡', $instructions);
        $this->assertStringNotContainsString('Hi Sarah', $instructions);
        $this->assertStringNotContainsString('A simple way to bring in more local conversations', $instructions);
    }

    public function test_agent_exposes_web_fetch_tool(): void
    {
        $agent = new WriteFirstContactEmailAgent;
        $toolsIterator = $agent->tools();
        $tools = iterator_to_array($toolsIterator);
        $webFetch = $tools[0] ?? null;

        $this->assertCount(1, $tools);
        $this->assertInstanceOf(WebFetch::class, $webFetch);
    }

    public function test_schema_requires_channel_subject_and_body(): void
    {
        $agent = new WriteFirstContactEmailAgent;
        $schemaFactory = new JsonSchemaTypeFactory;
        $schema = $agent->schema($schemaFactory);

        $this->assertArrayHasKey('channel', $schema);
        $this->assertArrayHasKey('subject', $schema);
        $this->assertArrayHasKey('body', $schema);
    }

    public function test_agent_throws_when_prompt_file_is_missing(): void
    {
        $path = base_path('docs/prompts/laravel_tools/write-first-contact-email.md');
        $file = File::partialMock();
        $file->shouldReceive('exists')
            ->with($path)
            ->andReturn(false);

        $agent = new WriteFirstContactEmailAgent;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('First contact email prompt file not found');

        $agent->instructions();
    }

    public function test_agent_throws_when_prompt_file_is_empty(): void
    {
        $path = base_path('docs/prompts/laravel_tools/write-first-contact-email.md');
        $file = File::partialMock();
        $file->shouldReceive('exists')
            ->with($path)
            ->andReturn(true);
        $file->shouldReceive('get')
            ->with($path)
            ->andReturn('   ');

        $agent = new WriteFirstContactEmailAgent;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('First contact email prompt file is empty');

        $agent->instructions();
    }
}
