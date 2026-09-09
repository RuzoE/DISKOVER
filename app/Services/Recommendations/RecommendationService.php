<?php

namespace App\Services\Recommendations;

use App\Enums\RecommendationPriority;
use App\Enums\RecommendationStatus;
use App\Enums\RoleSlug;
use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Punto de entrada del módulo de recomendaciones: genera/actualiza las
 * recomendaciones de un estudiante a partir del motor de reglas y gestiona su
 * seguimiento (aceptar / descartar / completar).
 */
class RecommendationService
{
    public function __construct(private readonly RecommendationEngine $engine) {}

    /**
     * Sincroniza las recomendaciones del estudiante con el resultado del motor.
     * Es idempotente: no duplica y respeta las decisiones del estudiante.
     *
     * @return int recomendaciones nuevas creadas
     */
    public function generateFor(User $student): int
    {
        if (! $student->hasRole(RoleSlug::Student)) {
            return 0;
        }

        $drafts = $this->engine->run($student);
        $existing = $student->recommendations()->get()->keyBy('signature');
        $created = 0;

        DB::transaction(function () use ($student, $drafts, $existing, &$created): void {
            // Recomendaciones abiertas cuya condición ya no aplica -> resueltas.
            foreach ($existing as $signature => $recommendation) {
                if ($recommendation->status->isOpen() && ! $drafts->has($signature)) {
                    $recommendation->update([
                        'status' => RecommendationStatus::Completed,
                        'responded_at' => now(),
                    ]);
                }
            }

            foreach ($drafts as $signature => $draft) {
                $current = $existing->get($signature);

                if ($current !== null) {
                    // Respeta descartes y resoluciones previas.
                    if (in_array($current->status, [RecommendationStatus::Dismissed, RecommendationStatus::Completed], true)) {
                        continue;
                    }

                    $current->update($draft->toAttributes() + ['generated_at' => now()]);

                    continue;
                }

                $student->recommendations()->create($draft->toAttributes() + [
                    'signature' => $signature,
                    'status' => RecommendationStatus::Pending,
                    'generated_at' => now(),
                ]);
                $created++;
            }
        });

        return $created;
    }

    public function respond(Recommendation $recommendation, RecommendationStatus $status): Recommendation
    {
        $recommendation->update([
            'status' => $status,
            'responded_at' => now(),
        ]);

        return $recommendation;
    }

    /**
     * @return Collection<int, Recommendation>
     */
    public function openFor(User $student): Collection
    {
        return $student->recommendations()
            ->whereIn('status', [RecommendationStatus::Pending->value, RecommendationStatus::Accepted->value])
            ->with(['subject', 'activity', 'content'])
            ->get()
            ->sortByDesc(fn (Recommendation $r) => [$r->priority->weight(), $r->generated_at?->timestamp])
            ->values();
    }

    /**
     * @return Collection<int, Recommendation>
     */
    public function resolvedFor(User $student, int $limit = 15): Collection
    {
        return $student->recommendations()
            ->whereIn('status', [RecommendationStatus::Dismissed->value, RecommendationStatus::Completed->value])
            ->latest('responded_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function stats(User $student): array
    {
        $rows = $student->recommendations()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'open' => (int) (($rows[RecommendationStatus::Pending->value] ?? 0) + ($rows[RecommendationStatus::Accepted->value] ?? 0)),
            'high' => $student->recommendations()
                ->whereIn('status', [RecommendationStatus::Pending->value, RecommendationStatus::Accepted->value])
                ->where('priority', RecommendationPriority::High->value)
                ->count(),
            'completed' => (int) ($rows[RecommendationStatus::Completed->value] ?? 0),
        ];
    }
}
