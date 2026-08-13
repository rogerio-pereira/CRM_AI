<?php

namespace Tests\Unit\Ai\Discovery;

use App\Ai\Contracts\DiscoveryAdapter;
use App\Ai\Discovery\AiLedPublicWebDiscoveryAdapter;
use App\Ai\Discovery\ProspectingDiscoveryAgent;
use Tests\TestCase;

class AiLedPublicWebDiscoveryAdapterTest extends TestCase
{
    public function test_adapter_is_bound_in_container(): void
    {
        $adapter = $this->app->make(DiscoveryAdapter::class);

        $this->assertInstanceOf(AiLedPublicWebDiscoveryAdapter::class, $adapter);
    }

    public function test_discover_normalizes_faked_ai_payload(): void
    {
        ProspectingDiscoveryAgent::fake([
            [
                'schema_version' => 1,
                'agent' => 'prospecting',
                'target_count' => 20,
                'region_priority' => ['Lakeland', 'Tampa'],
                'leads' => [
                    [
                        'name' => 'GreenSprout Lawn Care',
                        'company_name' => 'GreenSprout Lawn Care',
                        'contact_name' => 'Sarah Owner',
                        'email' => 'Hello@GreenSprout.example',
                        'phone' => '813-555-0100',
                        'website' => 'greensprout.example',
                        'social_links' => ['https://instagram.com/greensprout'],
                        'city' => 'Lakeland',
                        'state' => 'FL',
                        'lead_source' => 'other',
                        'source_urls' => ['https://example.com/listing'],
                        'observed_signals' => ['Outdated website'],
                        'likely_needs' => ['lead_generation'],
                        'why_good_fit' => 'Local service business needing steady leads.',
                        'confidence' => 'high',
                    ],
                    [
                        'name' => 'No Email Biz',
                        'company_name' => 'No Email Biz',
                        'email' => null,
                        'lead_source' => 'prospecting',
                    ],
                ],
                'skipped' => [
                    [
                        'name' => 'Big Chain Co',
                        'reason' => 'Franchise / too large for MVP ICP.',
                    ],
                ],
            ],
        ]);

        $adapter = $this->app->make(DiscoveryAdapter::class);

        $result = $adapter->discover([
            'limit' => 5,
        ]);

        $this->assertSame(1, $result['schema_version']);
        $this->assertSame('prospecting', $result['agent']);
        $this->assertSame(5, $result['target_count']);
        $this->assertCount(1, $result['leads']);
        $this->assertSame('hello@greensprout.example', $result['leads'][0]['email']);
        $this->assertSame('https://greensprout.example', $result['leads'][0]['website']);
        $this->assertSame('prospecting', $result['leads'][0]['lead_source']);
        $this->assertTrue(
            collect($result['skipped'])->contains(fn (array $row): bool => $row['name'] === 'No Email Biz'),
        );

        ProspectingDiscoveryAgent::assertPrompted(function ($prompt): bool {
            return str_contains($prompt->prompt, 'Discover up to 5 lead candidates');
        });
    }

    public function test_discover_respects_custom_instructions_option(): void
    {
        ProspectingDiscoveryAgent::fake([
            [
                'schema_version' => 1,
                'agent' => 'prospecting',
                'target_count' => 1,
                'region_priority' => ['Tampa'],
                'leads' => [],
                'skipped' => [],
            ],
        ]);

        $adapter = $this->app->make(DiscoveryAdapter::class);

        $result = $adapter->discover([
            'limit' => 1,
            'instructions' => 'Custom prospecting instructions for tests.',
            'region_priority' => ['Tampa'],
        ]);

        $this->assertSame([], $result['leads']);
        $this->assertSame(['Tampa'], $result['region_priority']);
    }
}
