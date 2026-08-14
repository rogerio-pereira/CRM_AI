<?php

namespace Tests\Unit\Ai\Discovery;

use App\Ai\Contracts\DiscoveryAdapter;
use App\Ai\Discovery\ProspectingDiscoveryAgent;
use App\Ai\Discovery\PublicWebDiscoveryAdapter;
use Laravel\Ai\Providers\Tools\WebFetch;
use Laravel\Ai\Providers\Tools\WebSearch;
use Laravel\Ai\Responses\AgentResponse;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PublicWebDiscoveryAdapterTest extends TestCase
{
    public function test_adapter_is_bound_in_container(): void
    {
        $adapter = $this->app->make(DiscoveryAdapter::class);

        $this->assertInstanceOf(PublicWebDiscoveryAdapter::class, $adapter);
    }

    public function test_discover_keeps_valid_leads_and_skips_invalid_email(): void
    {
        ProspectingDiscoveryAgent::fake([
            [
                'leads' => [
                    [
                        'company_name' => 'GreenSprout Lawn Care',
                        'contact_name' => 'Sarah',
                        'email' => 'Hello@GreenSprout.example',
                        'phone' => '813-555-0100',
                        'website' => 'greensprout.example',
                        'social_links' => ['https://instagram.com/greensprout'],
                        'why_good_fit' => 'Local service business.',
                        'observed_signals' => ['Outdated website'],
                    ],
                    [
                        'company_name' => 'No Email Biz',
                        'email' => '',
                        'website' => 'https://no-email.example',
                    ],
                ],
                'skipped' => [],
            ],
        ]);

        $adapter = $this->app->make(DiscoveryAdapter::class);

        $result = $adapter->discover([
            'limit' => 5,
            'instructions' => 'Approved prospecting instructions for tests.',
        ]);

        $leads = $result['leads'];
        $firstLead = $leads[0];

        $this->assertCount(1, $leads);
        $this->assertSame('hello@greensprout.example', $firstLead['email']);
        $this->assertSame('https://greensprout.example', $firstLead['website']);
        $this->assertSame('prospecting', $firstLead['lead_source']);

        ProspectingDiscoveryAgent::assertPrompted(function ($prompt): bool {
            $promptText = $prompt->prompt;

            return str_contains($promptText, 'Discover up to 5 lead candidates')
                && str_contains($promptText, 'Rank website work first');
        });
    }

    public function test_discover_requires_instructions(): void
    {
        $adapter = $this->app->make(DiscoveryAdapter::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Prospecting discovery requires approved prompt instructions.');

        $adapter->discover([
            'instructions' => '   ',
        ]);
    }

    public function test_discover_clamps_limit_and_maps_irregular_payloads(): void
    {
        ProspectingDiscoveryAgent::fake([
            [
                'leads' => [
                    'not-an-array',
                    [
                        'company_name' => '',
                        'email' => 'missing-name@example.com',
                    ],
                    [
                        'company_name' => 'First Valid Co',
                        'email' => 'first@example.com',
                        'website' => 123,
                        'social_links' => 'not-a-list',
                        'observed_signals' => 'not-a-list',
                    ],
                    [
                        'company_name' => 'Second Valid Co',
                        'email' => 'second@example.com',
                    ],
                ],
                'skipped' => [
                    'not-an-array',
                    [
                        'name' => 'Skipped Co',
                        'reason' => 'No public email',
                    ],
                ],
            ],
        ]);

        $adapter = $this->app->make(DiscoveryAdapter::class);

        $result = $adapter->discover([
            'limit' => 0,
            'instructions' => 'Approved prospecting instructions for tests.',
        ]);

        $leads = $result['leads'];
        $skipped = $result['skipped'];
        $firstLead = $leads[0];
        $firstSkipped = $skipped[0];
        $unknownSkipped = $skipped[1];

        $this->assertCount(1, $leads);
        $this->assertSame('First Valid Co', $firstLead['company_name']);
        $this->assertNull($firstLead['website']);
        $this->assertSame([], $firstLead['social_links']);
        $this->assertSame([], $firstLead['observed_signals']);
        $this->assertSame('Skipped Co', $firstSkipped['name']);
        $this->assertSame('No public email', $firstSkipped['reason']);
        $this->assertSame('Unknown', $unknownSkipped['name']);

        ProspectingDiscoveryAgent::assertPrompted(function ($prompt): bool {
            $promptText = $prompt->prompt;

            return str_contains($promptText, 'Discover up to 1 lead candidates');
        });
    }

    public function test_discover_treats_non_array_leads_and_skipped_as_empty(): void
    {
        ProspectingDiscoveryAgent::fake([
            [
                'leads' => 'not-an-array',
                'skipped' => 'not-an-array',
            ],
        ]);

        $adapter = $this->app->make(DiscoveryAdapter::class);

        $result = $adapter->discover([
            'instructions' => 'Approved prospecting instructions for tests.',
        ]);

        $this->assertSame([], $result['leads']);
        $this->assertSame([], $result['skipped']);
    }

    public function test_discovery_agent_exposes_provider_web_search_and_fetch_tools(): void
    {
        $agent = new ProspectingDiscoveryAgent('Approved prospecting instructions for tests.');
        $tools = [];

        foreach ($agent->tools() as $tool) {
            $tools[] = $tool;
        }

        $webSearch = $tools[0] ?? null;
        $webFetch = $tools[1] ?? null;

        $this->assertCount(2, $tools);
        $this->assertInstanceOf(WebSearch::class, $webSearch);
        $this->assertInstanceOf(WebFetch::class, $webFetch);
        $this->assertSame('Plant City', $webSearch->city);
        $this->assertSame('FL', $webSearch->region);
        $this->assertSame('US', $webSearch->country);
    }

    public function test_discover_throws_when_response_is_not_structured(): void
    {
        $unstructuredResponse = Mockery::mock(AgentResponse::class);

        $adapter = Mockery::mock(PublicWebDiscoveryAdapter::class)
                        ->makePartial()
                        ->shouldAllowMockingProtectedMethods();

        $adapter->shouldReceive('promptDiscovery')
                    ->once()
                    ->andReturn($unstructuredResponse);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Prospecting discovery did not return structured output.');

        $adapter->discover([
            'instructions' => 'Approved prospecting instructions for tests.',
        ]);
    }
}
