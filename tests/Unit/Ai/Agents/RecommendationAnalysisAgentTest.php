<?php

namespace Tests\Unit\Ai\Agents;

use App\Ai\Agents\RecommendationAnalysisAgent;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Tests\TestCase;

class RecommendationAnalysisAgentTest extends TestCase
{
    public function test_instructions_are_the_approved_prompt(): void
    {
        $agent = new RecommendationAnalysisAgent('Recommend next steps.');

        $this->assertSame('Recommend next steps.', $agent->instructions());
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
