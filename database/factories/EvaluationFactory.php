<?php

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Evaluation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evaluation>
 */
class EvaluationFactory extends Factory
{
    protected $model = Evaluation::class;

    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory()->state(['type' => ActivityType::Quiz->value]),
            'time_limit_minutes' => null,
            'max_attempts' => 1,
            'shuffle_questions' => false,
            'pass_score' => null,
        ];
    }
}
