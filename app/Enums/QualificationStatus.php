<?php

namespace App\Enums;

enum QualificationStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Qualified = 'qualified';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Processing => __('Processing'),
            self::Qualified => __('Qualified'),
            self::Failed => __('Failed'),
        };
    }

    public function badgeClasses(): string
    {
        if ($this === self::Pending) {
            return 'bg-status-neutral/20 text-status-neutral border-status-neutral/50';
        }

        if ($this === self::Processing) {
            return 'bg-ai/15 text-ai border-ai/30';
        }

        if ($this === self::Qualified) {
            return 'bg-status-success/20 text-status-success border-status-success/50';
        }

        return 'bg-status-danger/20 text-status-danger border-status-danger/50';
    }
}
