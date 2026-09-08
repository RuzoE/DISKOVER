<?php

namespace Database\Factories;

use App\Enums\GradeSource;
use App\Models\Activity;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grade>
 */
class GradeFactory extends Factory
{
    protected $model = Grade::class;

    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'student_id' => User::factory(),
            'attempt_id' => null,
            'score' => fake()->numberBetween(50, 100),
            'feedback' => null,
            'source' => GradeSource::Manual->value,
            'graded_by' => null,
            'graded_at' => now(),
        ];
    }
}
