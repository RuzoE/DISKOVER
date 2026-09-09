<?php

namespace App\Services\Immersive;

use App\Enums\ImmersiveSessionStatus;
use App\Models\ImmersiveExperience;
use App\Models\ImmersiveSession;
use App\Models\User;
use App\Services\Academic\GradeService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Ciclo de vida de una sesión inmersiva: la crea el estudiante desde la web,
 * Unity la consume vía API con el `launch_token` y devuelve el resultado.
 */
class ImmersiveSessionService
{
    public function __construct(private readonly GradeService $grades) {}

    /**
     * Devuelve la sesión «started» del estudiante para esa experiencia o crea
     * una nueva.
     */
    public function startOrResume(ImmersiveExperience $experience, User $student): ImmersiveSession
    {
        if (! $experience->isActive()) {
            throw ValidationException::withMessages([
                'experience' => 'Esta experiencia no está disponible.',
            ]);
        }

        $open = $experience->sessions()
            ->where('student_id', $student->id)
            ->where('status', ImmersiveSessionStatus::Started->value)
            ->first();

        if ($open && ! $open->isExpired()) {
            return $open;
        }

        if ($open) {
            $open->update(['status' => ImmersiveSessionStatus::Expired]);
        }

        return $experience->sessions()->create([
            'student_id' => $student->id,
            'launch_token' => Str::random(64),
            'status' => ImmersiveSessionStatus::Started,
            'started_at' => now(),
        ]);
    }

    /**
     * Registra el resultado enviado por el cliente (Unity). Si la experiencia
     * está enlazada a una actividad, deriva la calificación.
     *
     * @param  array<string, mixed>  $payload
     */
    public function complete(ImmersiveSession $session, float $score, array $payload = []): ImmersiveSession
    {
        if (! $session->status->isOpen()) {
            throw ValidationException::withMessages(['session' => 'La sesión ya está cerrada.']);
        }

        if ($session->isExpired()) {
            $session->update(['status' => ImmersiveSessionStatus::Expired]);

            throw ValidationException::withMessages(['session' => 'La sesión ha caducado.']);
        }

        $experience = $session->experience;
        $max = (float) $experience->max_score;
        $score = max(0.0, min($score, $max));

        $session->update([
            'status' => ImmersiveSessionStatus::Completed,
            'score' => round($score, 2),
            'max_score' => $max,
            'payload' => $payload ?: null,
            'ended_at' => now(),
        ]);

        if ($experience->activity_id !== null && $experience->activity?->is_published) {
            $this->grades->setFromImmersive($experience->activity, $session->student, $score, $max);
        }

        return $session->refresh();
    }

    public function abandon(ImmersiveSession $session): ImmersiveSession
    {
        if ($session->status->isOpen()) {
            $session->update([
                'status' => ImmersiveSessionStatus::Abandoned,
                'ended_at' => now(),
            ]);
        }

        return $session;
    }
}
