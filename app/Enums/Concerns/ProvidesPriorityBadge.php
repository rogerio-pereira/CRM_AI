<?php

namespace App\Enums\Concerns;

trait ProvidesPriorityBadge
{
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Low => 'bg-status-neutral/20 text-status-neutral border-status-neutral/50',
            self::Medium => 'bg-status-warning/20 text-status-warning border-status-warning/50',
            self::High => 'bg-status-danger/15 text-status-danger border-status-danger/50',
        };
    }
}
