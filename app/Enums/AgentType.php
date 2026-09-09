<?php

namespace App\Enums;

enum AgentType: string
{
    case Prospecting = 'prospecting';
    case Qualification = 'qualification';
    case Recommendation = 'recommendation';
    case FirstContactEmail = 'first_contact_email';
    case ProposalAssistant = 'proposal_assistant';

    public function label(): string
    {
        return match ($this) {
            self::Prospecting => __('Prospecting'),
            self::Qualification => __('Qualification'),
            self::Recommendation => __('Recommendation'),
            self::FirstContactEmail => __('First contact email'),
            self::ProposalAssistant => __('Proposal assistant'),
        };
    }
}
