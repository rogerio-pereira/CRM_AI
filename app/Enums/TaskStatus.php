<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Done => __('Done'),
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-status-warning/20 text-status-warning border-status-warning/50',
            self::Done => 'bg-status-success/20 text-status-success border-status-success/50',
        };
    }
}
