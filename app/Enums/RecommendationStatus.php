<?php

namespace App\Enums;

enum RecommendationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Dismissed = 'dismissed';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Accepted => 'En curso',
            self::Dismissed => 'Descartada',
            self::Completed => 'Resuelta',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Accepted => 'blue',
            self::Dismissed => 'neutral',
            self::Completed => 'green',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Pending || $this === self::Accepted;
    }
}
