<?php

namespace Tests\Unit\Ai\Agents;

use App\Ai\Agents\QualificationAnalysisAgent;
use App\Ai\Tools\WriteFirstContactEmail;
use Laravel\Ai\Providers\Tools\WebFetch;
use Laravel\Ai\Providers\Tools\WebSearch;
use Tests\TestCase;

class QualificationAnalysisAgentTest extends TestCase
{
    public function test_agent_exposes_web_search_web_fetch_and_first_contact_email_tools(): void
    {
        $agent = new QualificationAnalysisAgent('Qualify this lead.');
        $toolsIterator = $agent->tools();
        $tools = iterator_to_array($toolsIterator);
        $webSearch = $tools[0] ?? null;
        $webFetch = $tools[1] ?? null;
        $writeFirstContactEmail = $tools[2] ?? null;

        $this->assertCount(3, $tools);
        $this->assertInstanceOf(WebSearch::class, $webSearch);
        $this->assertInstanceOf(WebFetch::class, $webFetch);
        $this->assertInstanceOf(WriteFirstContactEmail::class, $writeFirstContactEmail);
        $this->assertSame('write_first_contact_email', $writeFirstContactEmail->name());
        $this->assertSame('Qualify this lead.', $agent->instructions());
    }
}
