<?php

namespace App\Enums;

/**
 * Forma en que se entrega la experiencia inmersiva al estudiante. Laravel no
 * ejecuta la experiencia: sólo la registra y la enlaza (sección 19 del prompt).
 */
enum ImmersiveProvider: string
{
    case UnityWebgl = 'unity_webgl';
    case UnityStandalone = 'unity_standalone';
    case WebXr = 'webxr';
    case External = 'external';
    case Simulator = 'simulator';

    public function label(): string
    {
        return match ($this) {
            self::UnityWebgl => 'Unity WebGL (incrustado)',
            self::UnityStandalone => 'Unity de escritorio (enlace)',
            self::WebXr => 'WebXR',
            self::External => 'Enlace externo',
            self::Simulator => 'Simulador (pruebas)',
        };
    }

    /**
     * ¿Se incrusta en un iframe en la página del lanzador?
     */
    public function isEmbeddable(): bool
    {
        return in_array($this, [self::UnityWebgl, self::WebXr], true);
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
