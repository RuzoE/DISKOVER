<?php

namespace App\Enums;

enum RecommendationPriority: string
{
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function label(): string
    {
        return match ($this) {
            self::High => 'Alta',
            self::Medium => 'Media',
            self::Low => 'Baja',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::High => 'red',
            self::Medium => 'amber',
            self::Low => 'blue',
        };
    }

    /**
     * Peso para ordenar (mayor = más arriba).
     */
    public function weight(): int
    {
        return match ($this) {
            self::High => 3,
            self::Medium => 2,
            self::Low => 1,
        };
    }
}
