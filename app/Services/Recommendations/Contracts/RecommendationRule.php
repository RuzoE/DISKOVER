<?php

namespace App\Services\Recommendations\Contracts;

use App\DTOs\Recommendations\RecommendationDraft;
use App\Models\User;

/**
 * Una regla del motor de recomendaciones. Cada regla inspecciona los datos
 * reales del estudiante (progreso, notas, actividades) y devuelve cero o más
 * recomendaciones candidatas. Sin IA generativa.
 */
interface RecommendationRule
{
    /**
     * @return iterable<int, RecommendationDraft>
     */
    public function evaluate(User $student): iterable;
}
