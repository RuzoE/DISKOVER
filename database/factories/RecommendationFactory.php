<?php

namespace Database\Factories;

use App\Enums\RecommendationPriority;
use App\Enums\RecommendationStatus;
use App\Enums\RecommendationType;
use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Recommendation>
 */
class RecommendationFactory extends Factory
{
    protected $model = Recommendation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => RecommendationType::FocusSubject->value,
            'priority' => RecommendationPriority::Medium->value,
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'reason' => [],
            'status' => RecommendationStatus::Pending->value,
            'signature' => 'test:'.Str::random(10),
            'generated_at' => now(),
        ];
    }

    public function dismissed(): static
    {
        return $this->state(fn () => [
            'status' => RecommendationStatus::Dismissed->value,
            'responded_at' => now(),
        ]);
    }
}
