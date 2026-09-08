<?php

namespace Database\Factories;

use App\Enums\AcademicStatus;
use App\Models\Course;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'teacher_id' => null,
            'code' => strtoupper(Str::random(4)).fake()->unique()->numberBetween(10, 99),
            'name' => fake()->randomElement(['Unidad', 'Módulo', 'Tema']).' '.fake()->word(),
            'description' => fake()->sentence(),
            'position' => fake()->numberBetween(0, 10),
            'status' => AcademicStatus::Active->value,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => AcademicStatus::Draft->value]);
    }
}
