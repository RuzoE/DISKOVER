<?php

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'type' => ActivityType::Task->value,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'instructions' => fake()->sentence(),
            'max_score' => 100,
            'opens_at' => null,
            'due_at' => now()->addWeek(),
            'position' => fake()->numberBetween(1, 5),
            'is_published' => true,
        ];
    }

    public function quiz(): static
    {
        return $this->state(fn () => ['type' => ActivityType::Quiz->value])
            ->afterCreating(fn (Activity $activity) => $activity->evaluation()->firstOrCreate([]));
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['due_at' => now()->subDay()]);
    }
}
