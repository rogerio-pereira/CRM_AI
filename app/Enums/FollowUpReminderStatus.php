<?php

namespace App\Enums;

enum FollowUpReminderStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Completed => __('Completed'),
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-status-warning/20 text-status-warning border-status-warning/50',
            self::Completed => 'bg-status-success/20 text-status-success border-status-success/50',
        };
    }
}
