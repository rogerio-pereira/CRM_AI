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
}
