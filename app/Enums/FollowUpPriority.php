<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesPriorityBadge;

enum FollowUpPriority: string
{
    use ProvidesPriorityBadge;
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => __('Low'),
            self::Medium => __('Medium'),
            self::High => __('High'),
        };
    }
}
