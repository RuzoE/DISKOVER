<?php

namespace App\Services\AI;

use App\Enums\RoleSlug;
use App\Models\Activity;
use App\Models\User;
use App\Services\Academic\StudentWorkloadService;
use App\Services\Analytics\PerformanceAnalyticsService;
use App\Services\Analytics\StudentAnalyticsService;
use Illuminate\Support\Str;

/**
 * Construye un resumen de texto con el contexto académico del usuario para
 * alimentar al asistente. Sólo aporta datos reales del propio usuario.
 *
 * Minimización de datos (ADR-0017): sólo el primer nombre; nunca correo,
 * documento, identificadores internos, datos de otros estudiantes ni las
 * respuestas u opciones correctas de las evaluaciones.
 */
class AcademicContextBuilder
{
    private const STATUS_LABELS = [
        'pending' => 'pendiente',
        'overdue' => 'vencida',
        'submitted' => 'entregada sin calificar',
    ];

    public function __construct(
        private readonly StudentAnalyticsService $studentAnalytics,
        private readonly PerformanceAnalyticsService $performance,
        private readonly StudentWorkloadService $workload,
    ) {}

    public function for(User $user): string
    {
        $name = self::firstName($user);

        if (! $user->hasRole(RoleSlug::Student)) {
            return "CONTEXTO ACADÉMICO\nEl usuario ({$name}) no es estudiante; "
                .'responde de forma general sobre el uso de la plataforma.';
        }

        $profile = $this->studentAnalytics->profile($user);
        $report = $this->performance->studentReport($user);

        if ($profile['courses_count'] === 0) {
            return "CONTEXTO ACADÉMICO\nEl estudiante {$name} aún no está inscrito en ningún curso.";
        }

        $courses = collect($profile['courses'])
            ->map(fn ($row) => sprintf(
                '%s (%.0f%% progreso%s)',
                $row['course']->name,
                $row['progress']['percentage'],
                $row['progress']['average'] !== null ? ', nota media '.$row['progress']['average'].'%' : '',
            ))
            ->implode(' · ');

        $weakSubjects = collect($report['weak_subjects'])->map(fn ($s) => $s['name'])->all();
        $weakActivities = collect($report['weak_activities'])
            ->map(fn ($a) => $a['title'].' ('.$a['subject'].')')
            ->take(4)
            ->all();

        $lines = [
            'CONTEXTO ACADÉMICO',
            'Fecha actual: '.now()->format('d/m/Y H:i').'.',
            "Estudiante: {$name}.",
            'Cursos: '.$courses.'.',
            'Progreso global: '.$profile['overall_percentage'].'%. '
                .'Promedio general: '.($profile['overall_average'] !== null ? $profile['overall_average'].'%' : 'sin datos').'.',
            'Actividades pendientes: '.$profile['pending'].'. Vencidas: '.$profile['overdue'].'.',
            ...$this->pendingSection($user),
            ...$this->contentsSection($user),
            ...$this->gradesSection($user),
        ];

        if ($weakActivities !== []) {
            $lines[] = 'Actividades con baja nota: '.implode(', ', $weakActivities).'.';
        }

        $lines[] = 'A REFORZAR: '.($weakSubjects !== [] ? implode('; ', $weakSubjects) : 'ninguna asignatura por debajo del umbral');

        return implode("\n", $lines);
    }

    public static function firstName(User $user): string
    {
        return (string) Str::of($user->name)->trim()->before(' ') ?: 'estudiante';
    }

    /**
     * @return array<int, string>
     */
    private function pendingSection(User $user): array
    {
        $pending = $this->workload->pendingActivities($user, 10);

        if ($pending->isEmpty()) {
            return ['TRABAJOS PENDIENTES: ninguno.'];
        }

        $lines = ['TRABAJOS PENDIENTES (por fecha límite):'];
        foreach ($pending as $row) {
            $lines[] = '- '.$this->describe($row['activity']).' — '.self::STATUS_LABELS[$row['status']].'.';
        }

        $soon = $pending->filter(fn ($row) => $row['status'] === 'pending'
            && $row['activity']->due_at !== null
            && $row['activity']->due_at->lte(now()->addDays(7)));

        $lines[] = 'VENCEN EN LOS PRÓXIMOS 7 DÍAS: '.($soon->isEmpty()
            ? 'ninguna.'
            : $soon->map(fn ($row) => $row['activity']->title.' ('.$row['activity']->due_at->format('d/m/Y').')')->implode('; ').'.');

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    private function contentsSection(User $user): array
    {
        $contents = $this->workload->uncompletedContents($user, 5);

        return ['CONTENIDOS SIN COMPLETAR: '.($contents->isEmpty()
            ? 'ninguno.'
            : $contents->map(fn ($c) => $c->title.' ('.$c->subject?->name.')')->implode('; ').'.')];
    }

    /**
     * @return array<int, string>
     */
    private function gradesSection(User $user): array
    {
        $grades = $this->workload->recentGrades($user, 3);

        if ($grades->isEmpty()) {
            return ['ÚLTIMAS CALIFICACIONES: sin calificaciones todavía.'];
        }

        $lines = ['ÚLTIMAS CALIFICACIONES:'];
        foreach ($grades as $grade) {
            $percent = $grade->percentage();
            $lines[] = sprintf(
                '- %s (%s): %s%s',
                $grade->activity->title,
                $grade->activity->subject?->name,
                $percent !== null ? $percent.'%' : (float) $grade->score.' pts',
                filled($grade->feedback) ? '. Retroalimentación del docente: «'.Str::limit(trim($grade->feedback), 200).'»' : '',
            );
        }

        return $lines;
    }

    private function describe(Activity $activity): string
    {
        return sprintf(
            '%s — %s — %s — %s',
            $activity->title,
            $activity->subject?->name,
            $activity->isQuiz() ? 'cuestionario' : 'tarea',
            $activity->due_at ? 'vence '.$activity->due_at->format('d/m/Y H:i') : 'sin fecha límite',
        );
    }
}
