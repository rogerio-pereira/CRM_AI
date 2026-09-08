<?php

namespace Tests\Feature\Prospecting;

use App\Ai\Agents\ProspectingAgent;
use App\Ai\Contracts\DiscoveryAdapter;
use App\Ai\Discovery\ProspectingDiscoveryAgent;
use App\Enums\PipelineStage;
use App\Jobs\RunProspectingAgentJob;
use App\Jobs\RunQualificationAgentJob;
use App\Models\Client;
use App\Models\Opportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ProspectingAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_creates_lead_opportunity_and_enqueues_qualification(): void
    {
        Queue::fake([
            RunQualificationAgentJob::class,
        ]);

        ProspectingDiscoveryAgent::fake([
            [
                'leads' => [
                    [
                        'company_name' => 'GreenSprout Lawn Care',
                        'contact_name' => 'Sarah Owner',
                        'email' => 'sarah@greensprout.example',
                        'phone' => '813-555-0100',
                        'website' => 'https://greensprout.example',
                        'social_links' => ['https://instagram.com/greensprout'],
                        'why_good_fit' => 'Local lawn care business that may need steadier leads.',
                        'observed_signals' => ['Referral-heavy local service'],
                    ],
                ],
                'skipped' => [],
            ],
        ]);

        $agent = app(ProspectingAgent::class);

        $result = $agent->handle([
            'triggered_by' => 'prospecting:run',
            'limit' => 1,
        ]);

        $this->assertSame('completed', $result['status']);
        $this->assertSame(1, $result['created_count']);

        $client = Client::query()
                        ->where('company_name', 'GreenSprout Lawn Care')
                        ->first();

        $this->assertNotNull($client);
        $this->assertSame('prospecting', $client->lead_source);
        $this->assertSame('sarah@greensprout.example', $client->contact_email);

        $socialLinks = $client->social_links;
        $firstSocialLink = $socialLinks[0] ?? null;
        $platform = $firstSocialLink['platform'] ?? null;

        $this->assertSame('Web', $platform);

        $opportunity = Opportunity::query()
                            ->where('client_id', $client->id)
                            ->first();

        $this->assertNotNull($opportunity);
        $this->assertSame(PipelineStage::Lead, $opportunity->stage);
        $this->assertSame($client->company_name, $opportunity->title);

        Queue::assertPushed(RunQualificationAgentJob::class, 1);
        Queue::assertPushed(RunQualificationAgentJob::class, function (RunQualificationAgentJob $job) use ($opportunity): bool {
            $payloadOpportunityId = $job->payload['opportunity_id'] ?? null;

            return $payloadOpportunityId === $opportunity->id;
        });
    }

    public function test_agent_searches_again_when_the_first_lead_is_a_duplicate(): void
    {
        Queue::fake([
            RunQualificationAgentJob::class,
        ]);

        Client::factory()
            ->create([
                'company_name' => 'Existing By Name',
                'website' => 'https://unique-name.example',
                'contact_email' => 'name@example.com',
                'contact_phone' => '8135550001',
            ]);

        $discovery = Mockery::mock(DiscoveryAdapter::class);
        $discovery->shouldReceive('discover')
            ->twice()
            ->andReturn(
                [
                    'leads' => [
                        [
                            'company_name' => 'Existing By Name',
                            'email' => 'new1@example.com',
                            'website' => 'https://brand-new-1.example',
                        ],
                    ],
                    'skipped' => [],
                ],
                [
                    'leads' => [
                        [
                            'company_name' => 'Fresh Pool Co',
                            'email' => 'hello@freshpool.example',
                            'website' => 'https://freshpool.example',
                        ],
                    ],
                    'skipped' => [],
                ],
            );

        $this->app->instance(DiscoveryAdapter::class, $discovery);

        $agent = app(ProspectingAgent::class);

        $result = $agent->handle([
            'limit' => 1,
        ]);

        $this->assertSame(1, $result['created_count']);
        $this->assertDatabaseHas('clients', [
            'company_name' => 'Fresh Pool Co',
            'lead_source' => 'prospecting',
        ]);
        Queue::assertPushed(RunQualificationAgentJob::class, 1);
    }

    public function test_agent_searches_again_when_discovery_returns_no_valid_email(): void
    {
        Queue::fake([
            RunQualificationAgentJob::class,
        ]);

        $discovery = Mockery::mock(DiscoveryAdapter::class);
        $discovery->shouldReceive('discover')
            ->twice()
            ->andReturn(
                [
                    'leads' => [],
                    'skipped' => [
                        [
                            'name' => 'No Email Biz',
                            'reason' => 'Missing company name or valid public email.',
                        ],
                    ],
                ],
                [
                    'leads' => [
                        [
                            'company_name' => 'Contactable Co',
                            'email' => 'hello@contactable.example',
                        ],
                    ],
                    'skipped' => [],
                ],
            );

        $this->app->instance(DiscoveryAdapter::class, $discovery);

        $agent = app(ProspectingAgent::class);

        $result = $agent->handle([
            'limit' => 1,
        ]);

        $this->assertSame(1, $result['created_count']);
        $this->assertDatabaseHas('clients', [
            'company_name' => 'Contactable Co',
            'lead_source' => 'prospecting',
        ]);
    }

    public function test_agent_throws_when_no_unique_contactable_lead_is_found(): void
    {
        $discovery = Mockery::mock(DiscoveryAdapter::class);
        $discovery->shouldReceive('discover')
            ->times(5)
            ->andReturn([
                'leads' => [],
                'skipped' => [
                    [
                        'name' => 'No Email Biz',
                        'reason' => 'Missing company name or valid public email.',
                    ],
                ],
            ]);

        $this->app->instance(DiscoveryAdapter::class, $discovery);

        $agent = app(ProspectingAgent::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Prospecting could not find a unique contactable lead.');

        $agent->handle([
            'limit' => 1,
        ]);
    }

    public function test_command_job_persists_leads_with_mocked_discovery(): void
    {
        Queue::fake();

        ProspectingDiscoveryAgent::fake([
            [
                'leads' => [
                    [
                        'company_name' => 'BrightPool Service',
                        'email' => 'hello@brightpool.example',
                        'website' => 'https://brightpool.example',
                        'why_good_fit' => 'Recurring local service with follow-up upside.',
                    ],
                ],
                'skipped' => [],
            ],
        ]);

        config([
            'prospecting.default_limit' => 1,
        ]);

        $this->artisan('prospecting:run')
            ->assertSuccessful();

        /** @var RunProspectingAgentJob|null $dispatched */
        $dispatched = null;

        Queue::assertPushed(RunProspectingAgentJob::class, 1);
        Queue::assertPushed(RunProspectingAgentJob::class, function (RunProspectingAgentJob $job) use (&$dispatched): bool {
            $triggeredBy = $job->payload['triggered_by'] ?? null;
            $limit = $job->payload['limit'] ?? null;
            $dispatched = $job;

            if ($triggeredBy !== 'prospecting:run') {
                return false;
            }

            return $limit === 1;
        });

        $this->assertNotNull($dispatched);
        $dispatched->handle();

        $this->assertDatabaseHas('clients', [
            'company_name' => 'BrightPool Service',
            'lead_source' => 'prospecting',
            'contact_email' => 'hello@brightpool.example',
        ]);
    }

    public function test_agent_skips_invalid_social_links(): void
    {
        Queue::fake([
            RunQualificationAgentJob::class,
        ]);

        $discoveredPayload = [
            'leads' => [
                [
                    'company_name' => 'Mixed Social Co',
                    'email' => 'hello@mixedsocial.example',
                    'social_links' => [
                        123,
                        '   ',
                        'https://instagram.com/mixedsocial',
                    ],
                ],
            ],
            'skipped' => [],
        ];

        $discovery = Mockery::mock(DiscoveryAdapter::class);
        $discovery->shouldReceive('discover')
            ->once()
            ->andReturn($discoveredPayload);

        $this->app->instance(DiscoveryAdapter::class, $discovery);

        $agent = app(ProspectingAgent::class);

        $result = $agent->handle([
            'limit' => 1,
        ]);

        $mixedSocial = Client::query()
                            ->where('company_name', 'Mixed Social Co')
                            ->first();

        $this->assertSame(1, $result['created_count']);
        $this->assertNotNull($mixedSocial);
        $this->assertSame(
            [
                [
                    'platform' => 'Web',
                    'url' => 'https://instagram.com/mixedsocial',
                ],
            ],
            $mixedSocial->social_links,
        );
    }

    public function test_agent_stores_empty_social_links_when_value_is_not_a_list(): void
    {
        Queue::fake([
            RunQualificationAgentJob::class,
        ]);

        $discovery = Mockery::mock(DiscoveryAdapter::class);
        $discovery->shouldReceive('discover')
            ->once()
            ->andReturn([
                'leads' => [
                    [
                        'company_name' => 'No Social List Co',
                        'email' => 'hello@nosocial.example',
                        'social_links' => 'not-a-list',
                    ],
                ],
                'skipped' => [],
            ]);

        $this->app->instance(DiscoveryAdapter::class, $discovery);

        $agent = app(ProspectingAgent::class);

        $result = $agent->handle([
            'limit' => 1,
        ]);

        $client = Client::query()
                        ->where('company_name', 'No Social List Co')
                        ->first();

        $this->assertSame(1, $result['created_count']);
        $this->assertNotNull($client);
        $this->assertSame([], $client->social_links);
    }

    public function test_agent_defaults_to_one_lead_per_job(): void
    {
        Queue::fake([
            RunQualificationAgentJob::class,
        ]);

        $discovery = Mockery::mock(DiscoveryAdapter::class);
        $discovery->shouldReceive('discover')
            ->once()
            ->with(Mockery::on(function (array $options): bool {
                $limit = $options['limit'] ?? null;
                $instructions = $options['instructions'] ?? null;
                $excludeCompanyNames = $options['exclude_company_names'] ?? null;

                if ($limit !== 1) {
                    return false;
                }

                if (! is_string($instructions)) {
                    return false;
                }

                if ($instructions === '') {
                    return false;
                }

                return $excludeCompanyNames === [];
            }))
            ->andReturn([
                'leads' => [
                    [
                        'company_name' => 'One Lead Co',
                        'email' => 'hello@onelead.example',
                    ],
                ],
                'skipped' => [],
            ]);

        $this->app->instance(DiscoveryAdapter::class, $discovery);

        $agent = app(ProspectingAgent::class);

        $result = $agent->handle([]);

        $this->assertSame(1, $result['created_count']);
    }

    public function test_agent_passes_existing_companies_to_discovery(): void
    {
        Queue::fake([
            RunQualificationAgentJob::class,
        ]);

        Client::factory()
            ->create([
                'company_name' => 'Already In Crm',
            ]);

        $discovery = Mockery::mock(DiscoveryAdapter::class);
        $discovery->shouldReceive('discover')
            ->once()
            ->with(Mockery::on(function (array $options): bool {
                $excludeCompanyNames = $options['exclude_company_names'] ?? null;

                if (! is_array($excludeCompanyNames)) {
                    return false;
                }

                return in_array('Already In Crm', $excludeCompanyNames, true);
            }))
            ->andReturn([
                'leads' => [
                    [
                        'company_name' => 'Brand New Co',
                        'email' => 'hello@brandnew.example',
                    ],
                ],
                'skipped' => [],
            ]);

        $this->app->instance(DiscoveryAdapter::class, $discovery);

        $agent = app(ProspectingAgent::class);

        $result = $agent->handle([
            'limit' => 1,
        ]);

        $this->assertSame(1, $result['created_count']);
        $this->assertDatabaseHas('clients', [
            'company_name' => 'Brand New Co',
        ]);
    }

    public function test_agent_throws_when_prompt_file_is_missing(): void
    {
        File::partialMock()
            ->shouldReceive('exists')
            ->andReturn(false);

        $agent = app(ProspectingAgent::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Prospecting prompt file not found');

        $agent->handle([
            'limit' => 1,
        ]);
    }

    public function test_agent_throws_when_prompt_file_is_empty(): void
    {
        $file = File::partialMock();
        $file->shouldReceive('exists')
            ->andReturn(true);
        $file->shouldReceive('get')
            ->andReturn('   ');

        $agent = app(ProspectingAgent::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Prospecting prompt file is empty');

        $agent->handle([
            'limit' => 1,
        ]);
    }

    public function test_approved_prompt_analyzes_the_full_catalog_without_a_fixed_ranking(): void
    {
        $promptPath = base_path('docs/prompts/prospecting-agent.md');
        $prompt = File::get($promptPath);
        $promptText = (string) $prompt;

        $this->assertStringContainsString('independent outbound salesperson', $promptText);
        $this->assertStringContainsString('Do not apply a global service ranking', $promptText);
        $this->assertStringContainsString('Lead generation', $promptText);
        $this->assertStringContainsString('Content creation', $promptText);
        $this->assertStringContainsString('Email marketing', $promptText);
        $this->assertStringContainsString('Business automations', $promptText);
        $this->assertStringContainsString('Website design and development', $promptText);
        $this->assertStringContainsString('Custom software development', $promptText);
        $this->assertStringContainsString('later upsell', $promptText);
        $this->assertStringNotContainsString('Website design and development — primary entry', $promptText);
    }
}
