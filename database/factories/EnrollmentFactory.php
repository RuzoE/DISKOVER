<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'student_id' => User::factory(),
            'status' => EnrollmentStatus::Active->value,
            'enrolled_at' => now(),
        ];
    }

    public function withdrawn(): static
    {
        return $this->state(fn () => ['status' => EnrollmentStatus::Withdrawn->value]);
    }
}
