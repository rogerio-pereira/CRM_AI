<?php

namespace App\Enums;

enum OpportunityStage: string
{
    case Lead = 'lead';
    case Qualification = 'qualification';
    case Contact = 'contact';
    case ProposalGeneration = 'proposal_generation';
    case ProposalAnalysis = 'proposal_analysis';
    case ProposalSent = 'proposal_sent';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Lead => __('Lead'),
            self::Qualification => __('Qualification'),
            self::Contact => __('Contact'),
            self::ProposalGeneration => __('Proposal Generation'),
            self::ProposalAnalysis => __('Proposal Analysis'),
            self::ProposalSent => __('Proposal Sent'),
            self::Won => __('Won'),
            self::Lost => __('Lost'),
        };
    }

    public function colorToken(): string
    {
        return match ($this) {
            self::Lead => 'neutral',
            self::Qualification => 'ai',
            self::Contact => 'accent',
            self::ProposalGeneration => 'ai',
            self::ProposalAnalysis => 'accent',
            self::ProposalSent => 'neutral',
            self::Won => 'success',
            self::Lost => 'danger',
        };
    }

    public function headerClasses(): string
    {
        return match ($this->colorToken()) {
            'ai' => 'border-ai/40 bg-ai/10 text-ai-soft',
            'accent' => 'border-accent/40 bg-accent/10 text-accent-soft',
            'success' => 'border-success/40 bg-success/10 text-success',
            'danger' => 'border-danger/40 bg-danger/10 text-danger',
            default => 'border-border-default bg-app-hover text-text-muted',
        };
    }

    public function isTerminal(): bool
    {
        if ($this === self::Won) {
            return true;
        }

        if ($this === self::Lost) {
            return true;
        }

        return false;
    }

    /**
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [
            self::Lead,
            self::Qualification,
            self::Contact,
            self::ProposalGeneration,
            self::ProposalAnalysis,
            self::ProposalSent,
            self::Won,
            self::Lost,
        ];
    }
}
