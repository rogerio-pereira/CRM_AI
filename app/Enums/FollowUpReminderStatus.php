<?php

namespace App\Enums;

enum FollowUpReminderStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Snoozed = 'snoozed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Completed => __('Completed'),
            self::Snoozed => __('Snoozed'),
        };
    }
}
