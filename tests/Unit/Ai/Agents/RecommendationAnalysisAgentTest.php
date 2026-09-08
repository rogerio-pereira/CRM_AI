<?php

namespace Tests\Unit\Ai\Agents;

use App\Ai\Agents\RecommendationAnalysisAgent;
use App\Ai\Tools\WriteFirstContactEmail;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Tests\TestCase;

class RecommendationAnalysisAgentTest extends TestCase
{
    public function test_agent_exposes_first_contact_email_tool(): void
    {
        $agent = new RecommendationAnalysisAgent('Recommend next steps.');
        $toolsIterator = $agent->tools();
        $tools = iterator_to_array($toolsIterator);
        $writeFirstContactEmail = $tools[0] ?? null;

        $this->assertSame('Recommend next steps.', $agent->instructions());
        $this->assertCount(1, $tools);
        $this->assertInstanceOf(WriteFirstContactEmail::class, $writeFirstContactEmail);
        $this->assertSame('write_first_contact_email', $writeFirstContactEmail->name());
    }

    public function test_schema_requires_recommendation_payload_fields(): void
    {
        $agent = new RecommendationAnalysisAgent('Recommend next steps.');
        $schemaFactory = new JsonSchemaTypeFactory;
        $schema = $agent->schema($schemaFactory);

        $this->assertArrayHasKey('schema_version', $schema);
        $this->assertArrayHasKey('agent', $schema);
        $this->assertArrayHasKey('lead_id', $schema);
        $this->assertArrayHasKey('opportunity_id', $schema);
        $this->assertArrayHasKey('ai_recommendations', $schema);
    }
}
