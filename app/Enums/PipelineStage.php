<?php

namespace App\Enums;

enum PipelineStage: string
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

    public function requiresUserAction(): bool
    {
        if ($this === self::Contact) {
            return true;
        }

        if ($this === self::ProposalAnalysis) {
            return true;
        }

        return false;
    }

    public function columnClasses(): string
    {
        if ($this->requiresUserAction()) {
            return 'border-primary/70 bg-primary/10 ring-1 ring-primary/30 shadow-[0_0_24px_-8px] shadow-primary/25';
        }

        return 'border-border bg-surface';
    }

    public function columnHeadingClasses(): string
    {
        if ($this->requiresUserAction()) {
            return 'text-primary-focus';
        }

        return 'text-text-primary';
    }

    public function badgeClasses(): string
    {
        if ($this->requiresUserAction()) {
            return 'bg-primary/20 text-primary-focus border-primary/50';
        }

        if ($this->colorToken() === 'ai') {
            return 'bg-ai/15 text-ai border-ai/30';
        }

        if ($this->colorToken() === 'accent') {
            return 'bg-accent/15 text-accent border-accent/30';
        }

        if ($this->colorToken() === 'success') {
            return 'bg-status-success/15 text-status-success border-status-success/30';
        }

        if ($this->colorToken() === 'danger') {
            return 'bg-status-danger/15 text-status-danger border-status-danger/30';
        }

        return 'bg-status-neutral/15 text-status-neutral border-status-neutral/30';
    }

    public function slug(): string
    {
        return str_replace('_', '-', $this->value);
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
