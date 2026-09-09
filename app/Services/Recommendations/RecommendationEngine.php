<?php

namespace App\Services\Recommendations;

use App\DTOs\Recommendations\RecommendationDraft;
use App\Models\User;
use App\Services\Recommendations\Contracts\RecommendationRule;
use App\Services\Recommendations\Rules\OverdueActivitiesRule;
use App\Services\Recommendations\Rules\PositiveReinforcementRule;
use App\Services\Recommendations\Rules\RetryEvaluationRule;
use App\Services\Recommendations\Rules\UnreadContentRule;
use App\Services\Recommendations\Rules\UpcomingActivitiesRule;
use App\Services\Recommendations\Rules\WeakSubjectsRule;
use Illuminate\Support\Collection;

/**
 * Ejecuta todas las reglas y devuelve las recomendaciones candidatas,
 * deduplicadas por firma y ordenadas por prioridad. Motor determinista;
 * ninguna regla llama a IA generativa (sección 17 del prompt maestro).
 */
class RecommendationEngine
{
    /**
     * @var array<int, class-string<RecommendationRule>>
     */
    private const RULES = [
        OverdueActivitiesRule::class,
        WeakSubjectsRule::class,
        RetryEvaluationRule::class,
        UpcomingActivitiesRule::class,
        UnreadContentRule::class,
        PositiveReinforcementRule::class,
    ];

    /**
     * @return Collection<string, RecommendationDraft> indexada por firma
     */
    public function run(User $student): Collection
    {
        $drafts = collect();

        foreach (self::RULES as $ruleClass) {
            /** @var RecommendationRule $rule */
            $rule = app($ruleClass);

            foreach ($rule->evaluate($student) as $draft) {
                $drafts->put($draft->signature, $draft); // la primera regla que la emite gana
            }
        }

        return $drafts->sortByDesc(fn (RecommendationDraft $d) => $d->priority->weight());
    }
}
