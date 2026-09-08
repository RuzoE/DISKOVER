<?php

namespace App\Enums;

/**
 * Tipo de pregunta de una evaluación.
 *
 * - Single:   opción múltiple con una única respuesta correcta.
 * - Multiple: opción múltiple con varias respuestas correctas.
 * - Boolean:  verdadero / falso.
 * - Open:     respuesta abierta de texto; la califica el docente.
 */
enum QuestionType: string
{
    case Single = 'single';
    case Multiple = 'multiple';
    case Boolean = 'boolean';
    case Open = 'open';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Opción única',
            self::Multiple => 'Opción múltiple',
            self::Boolean => 'Verdadero / Falso',
            self::Open => 'Respuesta abierta',
        };
    }

    public function hasOptions(): bool
    {
        return $this !== self::Open;
    }

    public function isAutoGraded(): bool
    {
        return $this !== self::Open;
    }

    public function allowsMultipleSelection(): bool
    {
        return $this === self::Multiple;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $case) => $carry + [$case->value => $case->label()],
            [],
        );
    }
}
