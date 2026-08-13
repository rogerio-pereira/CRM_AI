<?php

namespace Tests\Feature\Prospecting;

use App\Ai\Agents\ProspectingAgent;
use App\Ai\Discovery\ProspectingDiscoveryAgent;
use App\Enums\PipelineStage;
use App\Jobs\RunProspectingAgentJob;
use App\Jobs\RunQualificationAgentJob;
use App\Models\Client;
use App\Models\Opportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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
            $this->discoveryPayload([
                [
                    'name' => 'GreenSprout Lawn Care',
                    'company_name' => 'GreenSprout Lawn Care',
                    'contact_name' => 'Sarah Owner',
                    'email' => 'sarah@greensprout.example',
                    'phone' => '813-555-0100',
                    'website' => 'https://greensprout.example',
                    'social_links' => ['https://instagram.com/greensprout'],
                    'city' => 'Lakeland',
                    'state' => 'FL',
                    'lead_source' => 'prospecting',
                    'source_urls' => ['https://example.com/listing'],
                    'observed_signals' => ['Referral-heavy local service'],
                    'likely_needs' => ['lead_generation'],
                    'why_good_fit' => 'Local lawn care business that may need steadier leads.',
                    'confidence' => 'high',
                ],
            ]),
        ]);

        $result = app(ProspectingAgent::class)->handle([
            'triggered_by' => 'prospecting:run',
            'limit' => 5,
        ]);

        $this->assertSame('completed', $result['status']);
        $this->assertSame(1, $result['created_count']);
        $this->assertSame(0, $result['duplicate_count']);

        $client = Client::query()->where('company_name', 'GreenSprout Lawn Care')->first();

        $this->assertNotNull($client);
        $this->assertSame('prospecting', $client->lead_source);
        $this->assertSame('sarah@greensprout.example', $client->contact_email);
        $this->assertSame('Instagram', $client->social_links[0]['platform'] ?? null);

        $opportunity = Opportunity::query()->where('client_id', $client->id)->first();

        $this->assertNotNull($opportunity);
        $this->assertSame(PipelineStage::Lead, $opportunity->stage);

        Queue::assertPushed(RunQualificationAgentJob::class, function (RunQualificationAgentJob $job) use ($client): bool {
            return ($job->payload['client_id'] ?? null) === $client->id;
        });
    }

    public function test_agent_skips_duplicates_by_name_domain_email_and_phone(): void
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

        Client::factory()->create([
            'company_name' => 'Email Holder',
            'website' => 'https://email-holder.example',
            'contact_email' => 'dup@email.example',
            'contact_phone' => '8135550003',
        ]);

        Client::factory()->create([
            'company_name' => 'Phone Holder',
            'website' => 'https://phone-holder.example',
            'contact_email' => 'phone@example.com',
            'contact_phone' => '(813) 555-0004',
        ]);

        ProspectingDiscoveryAgent::fake([
            $this->discoveryPayload([
                [
                    'name' => 'Existing By Name',
                    'company_name' => 'Existing By Name',
                    'email' => 'new1@example.com',
                    'website' => 'https://brand-new-1.example',
                    'lead_source' => 'prospecting',
                ],
                [
                    'name' => 'Other Domain Biz',
                    'company_name' => 'Other Domain Biz',
                    'email' => 'new2@example.com',
                    'website' => 'https://domain-match.example',
                    'lead_source' => 'prospecting',
                ],
                [
                    'name' => 'Email Dup Biz',
                    'company_name' => 'Email Dup Biz',
                    'email' => 'dup@email.example',
                    'website' => 'https://brand-new-3.example',
                    'lead_source' => 'prospecting',
                ],
                [
                    'name' => 'Phone Dup Biz',
                    'company_name' => 'Phone Dup Biz',
                    'email' => 'new4@example.com',
                    'phone' => '813-555-0004',
                    'website' => 'https://brand-new-4.example',
                    'lead_source' => 'prospecting',
                ],
            ]),
        ]);

        $beforeClients = Client::query()->count();
        $beforeOpportunities = Opportunity::query()->count();

        $result = app(ProspectingAgent::class)->handle([
            'limit' => 10,
        ]);

        $this->assertSame(0, $result['created_count']);
        $this->assertSame(4, $result['duplicate_count']);
        $this->assertSame($beforeClients, Client::query()->count());
        $this->assertSame($beforeOpportunities, Opportunity::query()->count());
        Queue::assertNothingPushed();
    }

    public function test_manual_command_job_persists_leads_with_mocked_discovery(): void
    {
        Queue::fake();

        ProspectingDiscoveryAgent::fake([
            $this->discoveryPayload([
                [
                    'name' => 'BrightPool Service',
                    'company_name' => 'BrightPool Service',
                    'email' => 'hello@brightpool.example',
                    'website' => 'https://brightpool.example',
                    'lead_source' => 'prospecting',
                    'why_good_fit' => 'Recurring local service with follow-up upside.',
                ],
            ]),
        ]);

        $this->artisan('prospecting:run')->assertSuccessful();

        /** @var RunProspectingAgentJob|null $dispatched */
        $dispatched = null;

        Queue::assertPushed(RunProspectingAgentJob::class, function (RunProspectingAgentJob $job) use (&$dispatched): bool {
            $dispatched = $job;

            return ($job->payload['triggered_by'] ?? null) === 'prospecting:run';
        });

        $this->assertNotNull($dispatched);
        $dispatched->handle();

        $this->assertDatabaseHas('clients', [
            'company_name' => 'BrightPool Service',
            'lead_source' => 'prospecting',
            'contact_email' => 'hello@brightpool.example',
        ]);

        $client = Client::query()->where('company_name', 'BrightPool Service')->first();

        $this->assertNotNull($client);
        $this->assertDatabaseHas('opportunities', [
            'client_id' => $client->id,
            'stage' => PipelineStage::Lead->value,
        ]);

        Queue::assertPushed(RunQualificationAgentJob::class, function (RunQualificationAgentJob $job) use ($client): bool {
            return ($job->payload['client_id'] ?? null) === $client->id;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $leads
     * @return array<string, mixed>
     */
    private function discoveryPayload(array $leads): array
    {
        return [
            'schema_version' => 1,
            'agent' => 'prospecting',
            'target_count' => count($leads),
            'region_priority' => ['Lakeland', 'Tampa'],
            'leads' => $leads,
            'skipped' => [],
        ];
    }
}
