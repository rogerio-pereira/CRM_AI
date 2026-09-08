<?php

namespace App\Enums;

enum AgentType: string
{
    case Prospecting = 'prospecting';
    case Qualification = 'qualification';
    case FirstContactEmail = 'first_contact_email';
    case Recommendation = 'recommendation';
    case ProposalAssistant = 'proposal_assistant';

    public function label(): string
    {
        return match ($this) {
            self::Prospecting => __('Prospecting'),
            self::Qualification => __('Qualification'),
            self::FirstContactEmail => __('First contact email'),
            self::Recommendation => __('Recommendation'),
            self::ProposalAssistant => __('Proposal assistant'),
        };
    }
}
