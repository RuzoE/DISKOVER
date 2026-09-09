<?php

namespace Database\Factories;

use App\Enums\ImmersiveSessionStatus;
use App\Models\ImmersiveExperience;
use App\Models\ImmersiveSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ImmersiveSession>
 */
class ImmersiveSessionFactory extends Factory
{
    protected $model = ImmersiveSession::class;

    public function definition(): array
    {
        return [
            'immersive_experience_id' => ImmersiveExperience::factory(),
            'student_id' => User::factory(),
            'launch_token' => Str::random(64),
            'status' => ImmersiveSessionStatus::Started->value,
            'started_at' => now(),
        ];
    }

    public function completed(float $score = 80, float $max = 100): static
    {
        return $this->state(fn () => [
            'status' => ImmersiveSessionStatus::Completed->value,
            'score' => $score,
            'max_score' => $max,
            'ended_at' => now(),
        ]);
    }

    public function stale(): static
    {
        return $this->state(fn () => ['started_at' => now()->subDay()]);
    }
}
