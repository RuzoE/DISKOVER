<?php

namespace App\Enums;

/**
 * Rol de un mensaje dentro de una conversación con el asistente de IA.
 * Coincide con la convención de las APIs tipo OpenAI.
 */
enum AiMessageRole: string
{
    case System = 'system';
    case User = 'user';
    case Assistant = 'assistant';

    public function label(): string
    {
        return match ($this) {
            self::System => 'Sistema',
            self::User => 'Tú',
            self::Assistant => 'Asistente',
        };
    }
}
