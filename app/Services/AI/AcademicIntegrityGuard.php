<?php

namespace App\Services\AI;

use App\DTOs\AI\IntegrityVerdict;
use App\Enums\RoleSlug;
use App\Models\Question;
use App\Models\User;
use App\Services\Academic\StudentWorkloadService;
use Illuminate\Support\Str;

/**
 * Control de integridad académica en Laravel, ANTES de llamar al proveedor
 * (ADR-0017). Sólo aplica al rol estudiante; docentes y demás roles no tienen
 * restricciones. Reglas, por orden:
 *
 *  1. Intento de evaluación en curso            → bloquear.
 *  2. Enunciado o ≥2 opciones de una pregunta
 *     de una evaluación abierta                 → bloquear.
 *  3. Título o enunciado de una tarea pendiente → modo guiado.
 *  4. Petición de que le hagan el trabajo       → modo guiado.
 */
class AcademicIntegrityGuard
{
    public const REPLY_ATTEMPT_IN_PROGRESS = 'El asistente está en pausa mientras tienes una evaluación en curso. '
        .'Cuando la envíes podrás volver a consultarme. ¡Mucho ánimo!';

    public const REPLY_QUESTION_COPIED = 'Parece que tu mensaje incluye una pregunta de una evaluación que está abierta, '
        .'así que no puedo ayudarte con ella. Si quieres, te explico el tema general con un ejemplo distinto, '
        .'sin darte la respuesta.';

    /** Longitud mínima (normalizada) para comparar enunciados y títulos sin falsos positivos. */
    private const MIN_STATEMENT_LENGTH = 12;

    private const MIN_TITLE_LENGTH = 8;

    private const MIN_OPTION_LENGTH = 3;

    /**
     * Peticiones de resolver el trabajo o de saltarse las reglas (normalizadas).
     */
    private const WORK_REQUEST_PATTERNS = [
        'hazme', 'haz mi', 'haz la tarea', 'haz el trabajo', 'hazlo por mi', 'hagas mi', 'hagas la tarea',
        'resuelveme', 'resuelve la', 'resuelve el', 'resuelve este', 'resuelve esta', 'resuelvelo', 'resolverme',
        'dame las respuestas', 'dame la respuesta', 'dime las respuestas', 'dime la respuesta',
        'cual es la respuesta correcta', 'cual es la opcion correcta', 'que opcion es la correcta',
        'escribeme', 'redactame', 'escribe mi', 'redacta mi', 'escribe el ensayo', 'redacta el ensayo',
        'soy el profesor', 'soy la profesora', 'soy profesor', 'soy profesora', 'soy el docente', 'soy la docente', 'soy docente',
        'ignora tus reglas', 'ignora tus instrucciones', 'ignora las instrucciones', 'ignora las reglas', 'olvida tus instrucciones',
    ];

    public function __construct(private readonly StudentWorkloadService $workload) {}

    public function inspect(User $user, string $message): IntegrityVerdict
    {
        if (! config('dsle.ai.integrity', true) || ! $user->hasRole(RoleSlug::Student)) {
            return IntegrityVerdict::allow();
        }

        if ($this->workload->attemptInProgress($user) !== null) {
            return IntegrityVerdict::block('attempt_in_progress', self::REPLY_ATTEMPT_IN_PROGRESS);
        }

        $text = self::normalize($message);

        if ($this->containsOpenQuestion($user, $text)) {
            return IntegrityVerdict::block('open_question', self::REPLY_QUESTION_COPIED);
        }

        if ($this->mentionsPendingTask($user, $text)) {
            return IntegrityVerdict::guide('pending_task');
        }

        if ($this->asksToDoTheWork($text)) {
            return IntegrityVerdict::guide('work_request');
        }

        return IntegrityVerdict::allow();
    }

    /**
     * Minúsculas, sin tildes, sin signos y con espacios simples.
     */
    public static function normalize(string $text): string
    {
        return (string) Str::of(Str::ascii($text))
            ->lower()
            ->replaceMatches('/[^a-z0-9ñ ]+/u', ' ')
            ->squish();
    }

    private function containsOpenQuestion(User $user, string $text): bool
    {
        foreach ($this->workload->openQuizzes($user) as $activity) {
            foreach ($activity->evaluation->questions as $question) {
                if ($this->matchesQuestion($question, $text)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function matchesQuestion(Question $question, string $text): bool
    {
        $statement = self::normalize($question->statement);

        if (mb_strlen($statement) >= self::MIN_STATEMENT_LENGTH && $this->containsPhrase($text, $statement)) {
            return true;
        }

        $matchedOptions = $question->options
            ->map(fn ($option) => self::normalize($option->text))
            ->filter(fn ($option) => mb_strlen($option) >= self::MIN_OPTION_LENGTH)
            ->unique()
            ->filter(fn ($option) => $this->containsPhrase($text, $option))
            ->count();

        return $matchedOptions >= 2;
    }

    private function mentionsPendingTask(User $user, string $text): bool
    {
        foreach ($this->workload->pendingActivities($user) as $row) {
            $activity = $row['activity'];

            $title = self::normalize($activity->title);
            if (mb_strlen($title) >= self::MIN_TITLE_LENGTH && $this->containsPhrase($text, $title)) {
                return true;
            }

            foreach ([$activity->description, $activity->instructions] as $statement) {
                $statement = self::normalize((string) $statement);
                if (mb_strlen($statement) >= self::MIN_STATEMENT_LENGTH && $this->containsPhrase($text, $statement)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function asksToDoTheWork(string $text): bool
    {
        foreach (self::WORK_REQUEST_PATTERNS as $pattern) {
            if ($this->containsPhrase($text, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Coincidencia de frase completa (respetando límites de palabra).
     */
    private function containsPhrase(string $haystack, string $needle): bool
    {
        return str_contains(' '.$haystack.' ', ' '.$needle.' ');
    }
}
