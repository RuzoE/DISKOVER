<?php

namespace App\Enums;

/**
 * Tipo de recurso de contenido asociado a una asignatura.
 *
 * - Document / Video / Link: requieren una URL externa.
 * - Text: contenido redactado directamente en la plataforma (campo body).
 */
enum ContentType: string
{
    case Document = 'document';
    case Video = 'video';
    case Link = 'link';
    case Text = 'text';

    public function label(): string
    {
        return match ($this) {
            self::Document => 'Documento',
            self::Video => 'Vídeo',
            self::Link => 'Enlace',
            self::Text => 'Texto',
        };
    }

    public function requiresUrl(): bool
    {
        return $this !== self::Text;
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
