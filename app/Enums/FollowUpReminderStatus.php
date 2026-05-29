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
        if ($this === self::Completed) {
            return 'bg-status-success/20 text-status-success border-status-success/50';
        }

        return 'bg-status-neutral/20 text-status-neutral border-status-neutral/50';
    }
}
