<?php

namespace App\Enums;

enum ImmersiveSessionStatus: string
{
    case Started = 'started';
    case Completed = 'completed';
    case Abandoned = 'abandoned';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Started => 'En curso',
            self::Completed => 'Completada',
            self::Abandoned => 'Abandonada',
            self::Expired => 'Caducada',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Started => 'amber',
            self::Completed => 'green',
            self::Abandoned => 'neutral',
            self::Expired => 'red',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Started;
    }
}
