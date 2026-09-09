<?php

namespace App\Services\AI\Providers;

use App\DTOs\AI\ChatMessage;
use App\DTOs\AI\ChatResponse;
use App\Enums\AiMessageRole;
use App\Services\AI\Contracts\AiProvider;
use Illuminate\Support\Str;

/**
 * Proveedor de reserva: NO hace ninguna llamada de red. Genera una respuesta
 * educativa determinista a partir del último mensaje del usuario y del
 * contexto académico incluido en el hilo. Se usa cuando no hay proveedor real
 * configurado y en las pruebas.
 */
class StubAiProvider implements AiProvider
{
    public function name(): string
    {
        return 'stub';
    }

    public function model(): string
    {
        return 'dsle-stub-tutor';
    }

    public function chat(array $messages): ChatResponse
    {
        $lastUser = '';
        $context = '';

        foreach ($messages as $message) {
            /** @var ChatMessage $message */
            if ($message->role === AiMessageRole::User) {
                $lastUser = $message->content;
            }
            if ($message->role === AiMessageRole::System && str_contains($message->content, 'CONTEXTO ACADÉMICO')) {
                $context = $message->content;
            }
        }

        return new ChatResponse(
            content: $this->compose($lastUser, $context),
            model: $this->model(),
        );
    }

    private function compose(string $question, string $context): string
    {
        $intro = 'Asistente educativo de DSLE (modo sin conexión: configura `DSLE_AI_PROVIDER` '
            .'y `DSLE_AI_API_KEY` para respuestas generadas por IA).';

        $weak = $this->extractWeakPoints($context);
        $topic = Str::of($question)->lower()->trim();

        $tips = match (true) {
            $topic->contains(['examen', 'evaluación', 'evaluacion', 'test', 'quiz']) => [
                'Repasa primero los contenidos marcados como completados y luego los pendientes.',
                'Haz los cuestionarios de práctica hasta acertar al menos el 80 %.',
                'Anota las preguntas que fallas y revísalas el día antes.',
            ],
            $topic->contains(['no entiendo', 'ayuda', 'explica', 'cómo', 'como ']) => [
                'Divide el tema en 3 ideas clave y explícalas con tus propias palabras.',
                'Busca un ejemplo resuelto y reprodúcelo paso a paso sin mirar.',
                'Si sigues atascado, deja una duda concreta al docente en la actividad.',
            ],
            default => [
                'Fija un objetivo pequeño por sesión (un contenido o una actividad).',
                'Alterna estudio y auto-preguntas cada 25 minutos.',
                'Revisa tu progreso semanalmente en «Mi analítica».',
            ],
        };

        $lines = ["**{$intro}**", ''];

        if ($weak !== []) {
            $lines[] = 'Según tu contexto académico, conviene reforzar: **'.implode('**, **', $weak).'**.';
            $lines[] = '';
        }

        $lines[] = 'Sugerencias:';
        foreach ($tips as $tip) {
            $lines[] = '- '.$tip;
        }

        if ($question !== '') {
            $lines[] = '';
            $lines[] = '_Tu pregunta: «'.Str::limit(trim($question), 160).'»._';
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<int, string>
     */
    private function extractWeakPoints(string $context): array
    {
        if (! preg_match('/A REFORZAR:\s*(.+)/u', $context, $m)) {
            return [];
        }

        return collect(explode(';', $m[1]))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->take(3)
            ->values()
            ->all();
    }
}
