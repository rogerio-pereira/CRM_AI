<?php

namespace App\Enums;

enum ClientStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
    case Ignored = 'ignored';
    case ContactIntent = 'contact_intent';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Archived => __('Archived'),
            self::Ignored => __('Ignored'),
            self::ContactIntent => __('Contact intent'),
        };
    }

    /**
     * @return list<self>
     */
    public static function filterable(): array
    {
        return [
            self::Active,
            self::Archived,
            self::Ignored,
            self::ContactIntent,
        ];
    }
}
