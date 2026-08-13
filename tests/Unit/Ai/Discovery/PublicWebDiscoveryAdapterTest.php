<?php

namespace Tests\Unit\Ai\Discovery;

use App\Ai\Contracts\DiscoveryAdapter;
use App\Ai\Discovery\ProspectingDiscoveryAgent;
use App\Ai\Discovery\PublicWebDiscoveryAdapter;
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

        $this->assertCount(1, $result['leads']);
        $this->assertSame('hello@greensprout.example', $result['leads'][0]['email']);
        $this->assertSame('https://greensprout.example', $result['leads'][0]['website']);
        $this->assertSame('prospecting', $result['leads'][0]['lead_source']);

        ProspectingDiscoveryAgent::assertPrompted(function ($prompt): bool {
            return str_contains($prompt->prompt, 'Discover up to 5 lead candidates');
        });
    }
}
