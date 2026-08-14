<?php

namespace Tests\Support;

use App\Ai\Agents\QualificationAnalysisAgent;

class QualificationFake
{
    /**
     * @return array<string, mixed>
     */
    public static function successfulPayload(string $opportunityId = '1', string $clientId = '1'): array
    {
        return [
            'schema_version' => 1,
            'agent' => 'qualification',
            'opportunity_id' => $opportunityId,
            'client_id' => $clientId,
            'qualification_status' => 'qualified',
            'qualification_notes' => 'Local service business with a weak website and referral-heavy growth.',
            'qualification_last_error' => null,
            'retry_recommended' => null,
            'next_pipeline_stage' => 'contact',
            'ai_insights' => [
                'schema_version' => 1,
                'generated_at' => '2026-08-13T00:00:00Z',
                'source_agent' => 'qualification',
                'language' => 'en',
                'summary' => 'A local service business that could use a steadier flow of leads.',
                'fit' => [
                    'level' => 'high',
                    'label' => 'Ready to Contact',
                    'reason' => 'Public contact details and a practical growth gap are visible.',
                ],
                'pain_points' => [
                    [
                        'title' => 'Outdated website',
                        'evidence' => 'The public site looks dated and the next step is hard to find.',
                        'business_impact' => 'Visitors may keep looking instead of requesting a quote.',
                    ],
                ],
                'opportunities' => [
                    [
                        'service' => 'lead_generation',
                        'title' => 'Create a steadier local lead flow',
                        'why_it_matters' => 'Less dependence on referrals for new work.',
                        'priority' => 'high',
                    ],
                ],
                'outreach_strategy' => [
                    'positioning' => 'Helpful local growth conversation.',
                    'talking_points' => [
                        'The website may be losing quote requests.',
                    ],
                    'contact_example' => [
                        'channel' => 'email',
                        'subject' => 'A simple way to bring in more local conversations',
                        'body' => 'Hi there, I noticed a practical opportunity to turn more local demand into conversations.',
                    ],
                    'avoid' => [
                        'Technical jargon or pressure.',
                    ],
                ],
                'sources' => [
                    [
                        'label' => 'Company website',
                        'url' => 'https://example.com',
                        'observed_at' => '2026-08-13T00:00:00Z',
                    ],
                ],
                'confidence' => 'high',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function failedPayload(string $error = 'Not enough public information to qualify this lead.'): array
    {
        return [
            'schema_version' => 1,
            'agent' => 'qualification',
            'opportunity_id' => '1',
            'client_id' => '1',
            'qualification_status' => 'failed',
            'qualification_notes' => null,
            'qualification_last_error' => $error,
            'retry_recommended' => true,
            'ai_insights' => null,
            'next_pipeline_stage' => null,
        ];
    }

    /**
     * @param  list<array{service: string, title: string, why_it_matters: string, priority: string}>|null  $catalogOpportunities
     */
    public static function fakeSuccessful(
        ?string $opportunityId = null,
        ?string $clientId = null,
        ?array $catalogOpportunities = null,
    ): void {
        $payloadOpportunityId = $opportunityId ?? '1';
        $payloadClientId = $clientId ?? '1';
        $payload = self::successfulPayload($payloadOpportunityId, $payloadClientId);

        if ($catalogOpportunities !== null) {
            $payload['ai_insights']['opportunities'] = $catalogOpportunities;
        }

        QualificationAnalysisAgent::fake([
            $payload,
        ]);
    }

    public static function fakeFailed(string $error = 'Not enough public information to qualify this lead.'): void
    {
        QualificationAnalysisAgent::fake([
            self::failedPayload($error),
        ]);
    }
}
