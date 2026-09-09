<?php

namespace App\Services\AI;

use App\Enums\RoleSlug;
use App\Models\User;
use App\Services\Analytics\PerformanceAnalyticsService;
use App\Services\Analytics\StudentAnalyticsService;

/**
 * Construye un resumen de texto con el contexto académico del usuario para
 * alimentar al asistente. Sólo aporta datos reales del propio usuario.
 */
class AcademicContextBuilder
{
    public function __construct(
        private readonly StudentAnalyticsService $studentAnalytics,
        private readonly PerformanceAnalyticsService $performance,
    ) {}

    public function for(User $user): string
    {
        if (! $user->hasRole(RoleSlug::Student)) {
            return "CONTEXTO ACADÉMICO\nEl usuario ({$user->name}) no es estudiante; "
                .'responde de forma general sobre el uso de la plataforma.';
        }

        $profile = $this->studentAnalytics->profile($user);
        $report = $this->performance->studentReport($user);

        if ($profile['courses_count'] === 0) {
            return "CONTEXTO ACADÉMICO\nEl estudiante {$user->name} aún no está inscrito en ningún curso.";
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
            "Estudiante: {$user->name}.",
            'Cursos: '.$courses.'.',
            'Progreso global: '.$profile['overall_percentage'].'%. '
                .'Promedio general: '.($profile['overall_average'] !== null ? $profile['overall_average'].'%' : 'sin datos').'.',
            'Actividades pendientes: '.$profile['pending'].'. Vencidas: '.$profile['overdue'].'.',
        ];

        if ($weakActivities !== []) {
            $lines[] = 'Actividades con baja nota: '.implode(', ', $weakActivities).'.';
        }

        $lines[] = 'A REFORZAR: '.($weakSubjects !== [] ? implode('; ', $weakSubjects) : 'ninguna asignatura por debajo del umbral');

        return implode("\n", $lines);
    }
}
