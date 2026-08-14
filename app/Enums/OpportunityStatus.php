<?php

namespace App\Enums;

enum OpportunityStatus: string
{
    case Open = 'open';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Won => 'Won',
            self::Lost => 'Lost',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Open => 'Opportunity in progress (not won or lost yet).',
            self::Won => 'Closed as a win.',
            self::Lost => 'Closed as a loss.',
        };
    }
}
