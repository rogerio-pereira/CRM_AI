<?php

namespace App\Enums;

enum PipelineStage: string
{
    case Lead = 'lead';
    case Qualification = 'qualification';
    case Contact = 'contact';
    case ContactSent = 'contact_sent';
    case MeetingScheduled = 'meeting_scheduled';
    case ProposalGeneration = 'proposal_generation';
    case ProposalAnalysis = 'proposal_analysis';
    case ProposalSent = 'proposal_sent';
    case Won = 'won';
    case Lost = 'lost';
    case Disqualified = 'disqualified';

    public function label(): string
    {
        return match ($this) {
            self::Lead => __('Lead'),
            self::Qualification => __('Qualification'),
            self::Contact => __('Contact'),
            self::ContactSent => __('Contact Sent'),
            self::MeetingScheduled => __('Meeting Scheduled'),
            self::ProposalGeneration => __('Proposal Generation'),
            self::ProposalAnalysis => __('Proposal Analysis'),
            self::ProposalSent => __('Proposal Sent'),
            self::Won => __('Won'),
            self::Lost => __('Lost'),
            self::Disqualified => __('Disqualified'),
        };
    }

    public function colorToken(): string
    {
        return match ($this) {
            self::Lead => 'neutral',
            self::Qualification => 'ai',
            self::Contact => 'accent',
            self::ContactSent => 'accent',
            self::MeetingScheduled => 'accent',
            self::ProposalGeneration => 'ai',
            self::ProposalAnalysis => 'accent',
            self::ProposalSent => 'neutral',
            self::Won => 'success',
            self::Lost => 'danger',
            self::Disqualified => 'danger',
        };
    }

    public function requiresUserAction(): bool
    {
        $userActionStages = [
            self::Contact,
            self::ContactSent,
            self::MeetingScheduled,
            self::ProposalAnalysis,
        ];

        // If it belongs to the array this returns true; if not, false.
        return in_array($this, $userActionStages, true);
    }

    public function columnClasses(): string
    {
        if ($this->requiresUserAction()) {
            return 'kanban-column-user-action';
        }

        return 'border-border bg-surface';
    }

    public function columnHeadingClasses(): string
    {
        if ($this->requiresUserAction()) {
            return 'kanban-column-user-action-heading';
        }

        return 'text-text-primary';
    }

    public function badgeClasses(): string
    {
        if ($this->requiresUserAction()) {
            return 'bg-primary/20 text-primary-focus border-primary/50';
        }

        return $this->badgeClassesFromColorToken();
    }

    public function badgeClassesFromColorToken(): string
    {
        $colorToken = $this->colorToken();

        switch ($colorToken) {
            case 'ai':
                return 'bg-ai/15 text-ai border-ai/30';
            case 'accent':
                return 'bg-accent/15 text-accent border-accent/30';
            case 'success':
                return 'bg-status-success/15 text-status-success border-status-success/30';
            case 'danger':
                return 'bg-status-danger/15 text-status-danger border-status-danger/30';
            default:
                return 'bg-status-neutral/15 text-status-neutral border-status-neutral/30';
        }
    }

    public function slug(): string
    {
        return str_replace('_', '-', $this->value);
    }

    public function isTerminal(): bool
    {
        $terminalStages = [
            self::Won,
            self::Lost,
            self::Disqualified,
        ];

        // If it belongs to the array this returns true; if not, false.
        return in_array($this, $terminalStages, true);
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
            self::ContactSent,
            self::MeetingScheduled,
            self::ProposalGeneration,
            self::ProposalAnalysis,
            self::ProposalSent,
            self::Won,
            self::Lost,
            self::Disqualified,
        ];
    }
}
