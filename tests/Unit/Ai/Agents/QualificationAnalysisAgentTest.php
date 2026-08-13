<?php

namespace Tests\Unit\Ai\Agents;

use App\Ai\Agents\QualificationAnalysisAgent;
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
}
