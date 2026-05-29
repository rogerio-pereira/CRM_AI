<?php

namespace App\Enums;

enum ClientStatus: string
{
    case Active = 'active';
    case ContactIntent = 'contact_intent';
    case Ignored = 'ignored';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::ContactIntent => __('Contact intent'),
            self::Ignored => __('Ignored'),
            self::Archived => __('Archived'),
        };
    }

    public function colorToken(): string
    {
        if ($this === self::Active) {
            return 'success';
        }

        if ($this === self::ContactIntent) {
            return 'primary';
        }

        if ($this === self::Ignored) {
            return 'warning';
        }

        return 'neutral';
    }

    public function badgeClasses(): string
    {
        if ($this === self::Active) {
            return 'bg-status-success/20 text-status-success border-status-success/50';
        }

        if ($this === self::ContactIntent) {
            return 'bg-primary/20 text-primary-focus border-primary/50';
        }

        if ($this === self::Ignored) {
            return 'bg-status-warning/40 text-amber-300 border-status-warning/70 ring-1 ring-status-warning/30';
        }

        return 'bg-status-neutral/20 text-status-neutral border-status-neutral/50';
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }
}
