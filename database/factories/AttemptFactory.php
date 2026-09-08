<?php

namespace Database\Factories;

use App\Enums\AttemptStatus;
use App\Models\Attempt;
use App\Models\Evaluation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attempt>
 */
class AttemptFactory extends Factory
{
    protected $model = Attempt::class;

    public function definition(): array
    {
        return [
            'evaluation_id' => Evaluation::factory(),
            'student_id' => User::factory(),
            'number' => 1,
            'status' => AttemptStatus::InProgress->value,
            'score' => null,
            'max_score' => null,
            'started_at' => now(),
            'submitted_at' => null,
        ];
    }

    public function graded(float $score = 8, float $max = 10): static
    {
        return $this->state(fn () => [
            'status' => AttemptStatus::Graded->value,
            'score' => $score,
            'max_score' => $max,
            'submitted_at' => now(),
        ]);
    }
}
