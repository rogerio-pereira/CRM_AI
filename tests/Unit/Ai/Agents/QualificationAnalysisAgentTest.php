<?php

namespace Tests\Unit\Ai\Agents;

use App\Ai\Agents\QualificationAnalysisAgent;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\Providers\Tools\WebFetch;
use Laravel\Ai\Providers\Tools\WebSearch;
use Tests\TestCase;

class QualificationAnalysisAgentTest extends TestCase
{
    public function test_agent_exposes_web_search_and_web_fetch_tools(): void
    {
        $agent = new QualificationAnalysisAgent('Qualify this lead.');
        $toolsIterator = $agent->tools();
        $tools = iterator_to_array($toolsIterator);
        $webSearch = $tools[0] ?? null;
        $webFetch = $tools[1] ?? null;

        $this->assertCount(2, $tools);
        $this->assertInstanceOf(WebSearch::class, $webSearch);
        $this->assertInstanceOf(WebFetch::class, $webFetch);
        $this->assertSame('Qualify this lead.', $agent->instructions());
    }

    public function test_schema_includes_qualification_payload_fields(): void
    {
        $agent = new QualificationAnalysisAgent('Qualify this lead.');
        $schemaFactory = new JsonSchemaTypeFactory;
        $schema = $agent->schema($schemaFactory);

        $this->assertArrayHasKey('schema_version', $schema);
        $this->assertArrayHasKey('agent', $schema);
        $this->assertArrayHasKey('opportunity_id', $schema);
        $this->assertArrayHasKey('client_id', $schema);
        $this->assertArrayHasKey('qualification_status', $schema);
        $this->assertArrayHasKey('ai_insights', $schema);
    }
}
