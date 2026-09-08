<?php

namespace Database\Factories;

use App\Enums\AcademicStatus;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Programación Web', 'Bases de Datos', 'Redes', 'Inteligencia Artificial',
            'Diseño de Interfaces', 'Matemática Discreta', 'Sistemas Operativos',
        ]).' '.fake()->numberBetween(1, 3);

        return [
            'code' => strtoupper(Str::random(3)).'-'.fake()->unique()->numberBetween(100, 999),
            'name' => $name,
            'description' => fake()->paragraph(),
            'status' => AcademicStatus::Active->value,
            'starts_on' => now()->startOfMonth(),
            'ends_on' => now()->addMonths(4)->endOfMonth(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => AcademicStatus::Draft->value]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => AcademicStatus::Archived->value]);
    }
}
