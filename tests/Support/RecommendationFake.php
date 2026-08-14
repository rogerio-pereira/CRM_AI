<?php

namespace Tests\Support;

use App\Ai\Agents\RecommendationAnalysisAgent;

class RecommendationFake
{
    /**
     * @return array<string, mixed>
     */
    public static function successfulPayload(string $opportunityId = '1', string $leadId = '1'): array
    {
        return [
            'schema_version' => 1,
            'agent' => 'recommendation',
            'lead_id' => $leadId,
            'opportunity_id' => $opportunityId,
            'ai_recommendations' => [
                'schema_version' => 1,
                'generated_at' => '2026-08-14T00:00:00Z',
                'source_agent' => 'recommendation',
                'language' => 'en',
                'summary' => 'Start with a clearer website, then a simple follow-up conversation.',
                'pain_points' => [
                    [
                        'title' => 'Outdated website',
                        'evidence' => 'The public site looks dated and the next step is hard to find.',
                        'business_impact' => 'Visitors may keep looking instead of requesting a quote.',
                    ],
                ],
                'recommended_focus' => [
                    [
                        'service' => 'website_design_development',
                        'title' => 'Make the first impression easier to act on',
                        'why_it_matters' => 'A clearer site helps more visitors request a quote.',
                        'priority' => 'high',
                    ],
                ],
                'conversation_strategy' => [
                    'positioning' => 'Helpful local growth conversation.',
                    'talking_points' => [
                        'The website may be losing quote requests.',
                    ],
                    'contact_example' => [
                        'channel' => 'email',
                        'subject' => 'Helping more visitors feel ready to call',
                        'body' => 'Hi there, I noticed a practical opportunity to make the first visit easier to act on.',
                    ],
                    'questions_to_ask' => [
                        'Where do most new customers hear about you today?',
                    ],
                    'avoid' => [
                        'Technical jargon or pressure.',
                    ],
                ],
                'next_steps' => [
                    [
                        'title' => 'Review the example email before any outreach',
                        'reason' => 'A human should adapt the wording to this owner.',
                    ],
                ],
                'confidence' => 'high',
            ],
        ];
    }

    /**
     * Stored recommendation fields used by the insight panel in tests.
     *
     * @return array<string, mixed>
     */
    public static function panelRecommendations(): array
    {
        return [
            'outreach_strategy' => [
                'questions_to_ask' => [
                    'Where do most new customers hear about you today?',
                ],
            ],
            'next_steps' => [
                [
                    'title' => 'Review the example email before any outreach',
                    'reason' => 'A human should adapt the wording to this owner.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function incompletePayload(): array
    {
        $payload = self::successfulPayload();
        $payload['ai_recommendations']['summary'] = '';
        $payload['ai_recommendations']['conversation_strategy']['contact_example']['subject'] = '';
        $payload['ai_recommendations']['conversation_strategy']['contact_example']['body'] = '';

        return $payload;
    }

    public static function fakeSuccessful(
        ?string $opportunityId = null,
        ?string $leadId = null,
    ): void {
        $payloadOpportunityId = $opportunityId ?? '1';
        $payloadLeadId = $leadId ?? '1';
        $payload = self::successfulPayload($payloadOpportunityId, $payloadLeadId);

        RecommendationAnalysisAgent::fake([
            $payload,
        ]);
    }

    public static function fakeIncomplete(): void
    {
        RecommendationAnalysisAgent::fake([
            self::incompletePayload(),
        ]);
    }
}
