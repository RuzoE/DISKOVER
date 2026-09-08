<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Evaluation;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'evaluation_id' => Evaluation::factory(),
            'type' => QuestionType::Single->value,
            'statement' => fake()->sentence().' ?',
            'score' => 1,
            'position' => fake()->numberBetween(1, 10),
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => ['type' => QuestionType::Open->value]);
    }

    /**
     * Crea la pregunta de opción única con 3 opciones (la primera correcta).
     */
    public function withOptions(int $count = 3, int $correctIndex = 0): static
    {
        return $this->afterCreating(function (Question $question) use ($count, $correctIndex): void {
            for ($i = 0; $i < $count; $i++) {
                $question->options()->create([
                    'text' => 'Opción '.($i + 1),
                    'is_correct' => $i === $correctIndex,
                    'position' => $i + 1,
                ]);
            }
        });
    }
}
