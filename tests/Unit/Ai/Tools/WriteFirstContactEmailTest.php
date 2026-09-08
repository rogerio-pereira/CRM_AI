<?php

namespace Tests\Unit\Ai\Tools;

use App\Ai\Agents\WriteFirstContactEmailAgent;
use App\Ai\Tools\WriteFirstContactEmail;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Laravel\Ai\Tools\Request;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class WriteFirstContactEmailTest extends TestCase
{
    public function test_tool_exposes_name_description_and_required_brief_fields(): void
    {
        $tool = new WriteFirstContactEmail;
        $schemaFactory = new JsonSchemaTypeFactory;
        $schema = $tool->schema($schemaFactory);
        $description = (string) $tool->description();

        $this->assertSame('write_first_contact_email', $tool->name());
        $this->assertStringContainsString('contact_example', $description);
        $this->assertArrayHasKey('contact_name', $schema);
        $this->assertArrayHasKey('company_name', $schema);
        $this->assertArrayHasKey('line_of_business', $schema);
        $this->assertArrayHasKey('location', $schema);
        $this->assertArrayHasKey('service_angle', $schema);
        $this->assertArrayHasKey('observed_hook', $schema);
        $this->assertArrayHasKey('opportunity', $schema);
        $this->assertArrayHasKey('sample_insight', $schema);
    }

    public function test_handle_returns_structured_email_from_the_copywriting_agent(): void
    {
        $payload = [
                'channel' => 'email',
                'subject' => '🌱 Sarah, quotes walking past the lawn',
                'body' => "Hi Sarah,\n\nRoger Pereira\n[Front Porch Creative](https://frontporchcreative.io)",
            ];
        WriteFirstContactEmailAgent::fake([
            $payload,
        ]);

        $tool = new WriteFirstContactEmail;
        $request = $this->makeBriefRequest();
        $encoded = $tool->handle($request);
        $decoded = json_decode((string) $encoded, true);

        $this->assertSame($payload, $decoded);
        WriteFirstContactEmailAgent::assertPrompted(function ($prompt): bool {
            $promptText = $prompt->prompt;
            $hasCompany = str_contains($promptText, 'GreenSprout Lawn Care');
            $hasLineOfBusiness = str_contains($promptText, 'lawn care');
            $hasHook = str_contains($promptText, 'Most new work still comes from referrals.');

            if ($hasCompany === false) {
                return false;
            }

            if ($hasLineOfBusiness === false) {
                return false;
            }

            return $hasHook;
        });
    }

    public function test_handle_returns_plain_text_when_response_is_not_structured(): void
    {
        $plainText = 'Hi Sarah, this is a plain draft.';
        $unstructuredResponse = Mockery::mock(AgentResponse::class);
        $unstructuredResponse->text = $plainText;
        $copywriter = Mockery::mock(WriteFirstContactEmailAgent::class);
        $copywriter->shouldReceive('prompt')
            ->once()
            ->andReturn($unstructuredResponse);

        $this->app->bind(WriteFirstContactEmailAgent::class, function () use ($copywriter) {
            return $copywriter;
        });

        $tool = new WriteFirstContactEmail;
        $request = $this->makeBriefRequest();
        $result = $tool->handle($request);

        $this->assertSame($plainText, (string) $result);
    }

    public function test_handle_throws_when_structured_email_cannot_be_encoded(): void
    {
        $structuredResponse = Mockery::mock(StructuredAgentResponse::class);
        $structuredResponse->shouldReceive('toArray')
            ->once()
            ->andReturn([
                    'channel' => "\xB1\x31",
                    'subject' => 'Broken subject',
                    'body' => 'Broken body',
                ]);
        $copywriter = Mockery::mock(WriteFirstContactEmailAgent::class);
        $copywriter->shouldReceive('prompt')
            ->once()
            ->andReturn($structuredResponse);

        $this->app->bind(WriteFirstContactEmailAgent::class, function () use ($copywriter) {
            return $copywriter;
        });

        $tool = new WriteFirstContactEmail;
        $request = $this->makeBriefRequest();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('First contact email could not be encoded.');

        $tool->handle($request);
    }

    public function test_handle_throws_when_the_brief_cannot_be_encoded(): void
    {
        $tool = new WriteFirstContactEmail;
        $request = $this->makeBriefRequest("\xB1\x31");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('First contact email brief could not be encoded.');

        $tool->handle($request);
    }

    private function makeBriefRequest(string $contactName = 'Sarah'): Request
    {
        return new Request([
                'contact_name' => $contactName,
                'company_name' => 'GreenSprout Lawn Care',
                'line_of_business' => 'lawn care',
                'location' => 'Lakeland, FL',
                'service_angle' => 'lead_generation',
                'observed_hook' => 'Most new work still comes from referrals.',
                'opportunity' => 'Turn more local search into quote requests.',
                'sample_insight' => 'Show served neighborhoods next to the quote action.',
            ]);
    }
}
