<?php

namespace Database\Seeders;

use App\Enums\AcademicStatus;
use App\Enums\ContentType;
use App\Enums\EnrollmentStatus;
use App\Models\Content;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Estructura académica de ejemplo para desarrollo. Idempotente: se apoya en
 * el código de curso "DSLE-101".
 */
class DemoAcademicSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('email', 'docente@diskover.test')->first();
        $student = User::where('email', 'estudiante@diskover.test')->first();

        $course = Course::updateOrCreate(
            ['code' => 'DSLE-101'],
            [
                'name' => 'Fundamentos de DSLE',
                'description' => 'Curso de demostración del ecosistema de aprendizaje.',
                'status' => AcademicStatus::Active->value,
                'starts_on' => now()->startOfMonth(),
                'ends_on' => now()->addMonths(4)->endOfMonth(),
            ],
        );

        $subject = Subject::updateOrCreate(
            ['code' => 'DSLE-101-A'],
            [
                'course_id' => $course->id,
                'teacher_id' => $teacher?->id,
                'name' => 'Introducción a la plataforma',
                'description' => 'Primeros pasos y recorrido general.',
                'position' => 1,
                'status' => AcademicStatus::Active->value,
            ],
        );

        if ($subject->contents()->count() === 0) {
            Content::factory()->for($subject)->create([
                'title' => 'Bienvenida al curso',
                'type' => ContentType::Text->value,
                'created_by' => $teacher?->id,
                'position' => 1,
            ]);

            Content::factory()->for($subject)->link()->create([
                'title' => 'Guía de la plataforma (enlace)',
                'created_by' => $teacher?->id,
                'position' => 2,
            ]);
        }

        if ($student) {
            Enrollment::updateOrCreate(
                ['course_id' => $course->id, 'student_id' => $student->id],
                ['status' => EnrollmentStatus::Active->value, 'enrolled_at' => now()],
            );
        }
    }
}
