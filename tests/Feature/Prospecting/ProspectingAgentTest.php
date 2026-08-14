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
            'limit' => 5,
        ]);

        $this->assertSame('completed', $result['status']);
        $this->assertSame(1, $result['created_count']);
        $this->assertSame(0, $result['duplicate_count']);

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

    public function test_agent_skips_duplicates(): void
    {
        Queue::fake([
            RunQualificationAgentJob::class,
        ]);

        Client::factory()->create([
            'company_name' => 'Existing By Name',
            'website' => 'https://unique-name.example',
            'contact_email' => 'name@example.com',
            'contact_phone' => '8135550001',
        ]);

        Client::factory()->create([
            'company_name' => 'Domain Holder',
            'website' => 'https://www.domain-match.example',
            'contact_email' => 'domain@example.com',
            'contact_phone' => '8135550002',
        ]);

        ProspectingDiscoveryAgent::fake([
            [
                'leads' => [
                    [
                        'company_name' => 'Existing By Name',
                        'email' => 'new1@example.com',
                        'website' => 'https://brand-new-1.example',
                    ],
                    [
                        'company_name' => 'Other Domain Biz',
                        'email' => 'new2@example.com',
                        'website' => 'https://domain-match.example',
                    ],
                ],
                'skipped' => [],
            ],
        ]);

        $beforeClients = Client::query()->count();
        $agent = app(ProspectingAgent::class);

        $result = $agent->handle([
            'limit' => 10,
        ]);

        $afterClients = Client::query()->count();

        $this->assertSame(0, $result['created_count']);
        $this->assertSame(2, $result['duplicate_count']);
        $this->assertSame($beforeClients, $afterClients);
        Queue::assertNothingPushed();
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

        $this->artisan('prospecting:run')
            ->assertSuccessful();

        /** @var RunProspectingAgentJob|null $dispatched */
        $dispatched = null;

        Queue::assertPushed(RunProspectingAgentJob::class, function (RunProspectingAgentJob $job) use (&$dispatched): bool {
            $triggeredBy = $job->payload['triggered_by'] ?? null;
            $dispatched = $job;

            return $triggeredBy === 'prospecting:run';
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
                        'company_name' => 'No Social Co',
                        'email' => 'hello@nosocial.example',
                        'social_links' => 'not-an-array',
                    ],
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
            'limit' => 5,
        ]);

        $noSocial = Client::query()
                        ->where('company_name', 'No Social Co')
                        ->first();
        $mixedSocial = Client::query()
                            ->where('company_name', 'Mixed Social Co')
                            ->first();

        $this->assertSame(2, $result['created_count']);
        $this->assertNotNull($noSocial);
        $this->assertSame([], $noSocial->social_links);
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

    public function test_approved_prompt_ranks_website_as_the_primary_entry(): void
    {
        $promptPath = base_path('docs/prompts/prospecting-agent.md');
        $prompt = File::get($promptPath);
        $promptText = (string) $prompt;

        $this->assertStringContainsString('Website design and development — primary entry', $promptText);
        $this->assertStringContainsString('Custom software development — skip or lowest as the opening offer', $promptText);
        $this->assertStringContainsString('they could use email', $promptText);
    }
}
