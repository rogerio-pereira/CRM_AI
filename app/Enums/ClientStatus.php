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
