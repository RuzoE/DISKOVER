<?php

namespace Database\Factories;

use App\Enums\AcademicStatus;
use App\Enums\ImmersiveProvider;
use App\Models\ImmersiveExperience;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ImmersiveExperience>
 */
class ImmersiveExperienceFactory extends Factory
{
    protected $model = ImmersiveExperience::class;

    public function definition(): array
    {
        $title = 'Laboratorio virtual '.fake()->unique()->numberBetween(1, 999);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(4)),
            'description' => fake()->sentence(),
            'provider' => ImmersiveProvider::Simulator->value,
            'launch_url' => null,
            'config' => null,
            'max_score' => 100,
            'status' => AcademicStatus::Active->value,
            'activity_id' => null,
            'created_by' => null,
        ];
    }

    public function webgl(string $url = 'https://unity.example.test/build/index.html'): static
    {
        return $this->state(fn () => [
            'provider' => ImmersiveProvider::UnityWebgl->value,
            'launch_url' => $url,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => AcademicStatus::Draft->value]);
    }
}
