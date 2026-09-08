<?php

namespace Database\Factories;

use App\Enums\LearningEventType;
use App\Models\LearningEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LearningEvent>
 */
class LearningEventFactory extends Factory
{
    protected $model = LearningEvent::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => LearningEventType::ContentCompleted->value,
            'description' => fake()->sentence(),
            'payload' => null,
            'occurred_at' => now(),
        ];
    }
}
