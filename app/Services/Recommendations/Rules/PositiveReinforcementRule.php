<?php

namespace App\Services\Recommendations\Rules;

use App\DTOs\Recommendations\RecommendationDraft;
use App\Enums\RecommendationPriority;
use App\Enums\RecommendationType;
use App\Models\User;
use App\Services\Analytics\StudentAnalyticsService;
use App\Services\Recommendations\Contracts\RecommendationRule;

/**
 * Refuerzo positivo: si el estudiante va bien (progreso y promedio altos, sin
 * vencidas), se le anima a mantener el ritmo. Prioridad baja.
 */
class PositiveReinforcementRule implements RecommendationRule
{
    public function __construct(private readonly StudentAnalyticsService $analytics) {}

    public function evaluate(User $student): iterable
    {
        $profile = $this->analytics->profile($student);

        $goodProgress = $profile['overall_percentage'] >= 80;
        $goodAverage = $profile['overall_average'] !== null && $profile['overall_average'] >= 80;

        if ($profile['courses_count'] === 0 || $profile['overdue'] > 0 || ! $goodProgress || ! $goodAverage) {
            return;
        }

        yield new RecommendationDraft(
            type: RecommendationType::Positive,
            priority: RecommendationPriority::Low,
            title: '¡Vas muy bien!',
            body: sprintf(
                'Llevas un %s%% de progreso y un promedio del %s%%, sin actividades vencidas. Mantén el ritmo '
                .'y revisa tu evolución en «Mi analítica».',
                rtrim(rtrim(number_format($profile['overall_percentage'], 1), '0'), '.'),
                $profile['overall_average'],
            ),
            signature: 'positive:overall',
            reason: [
                'progress' => $profile['overall_percentage'],
                'average' => $profile['overall_average'],
            ],
        );
    }
}
