<?php

namespace Database\Seeders;

use App\Enums\AcademicStatus;
use App\Enums\ActivityType;
use App\Enums\ContentType;
use App\Enums\EnrollmentStatus;
use App\Enums\QuestionType;
use App\Models\Activity;
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

        // Trabajo de entrega (calificación manual).
        Activity::updateOrCreate(
            ['subject_id' => $subject->id, 'title' => 'Ensayo: expectativas del curso'],
            [
                'type' => ActivityType::Task->value,
                'description' => 'Redacta media página sobre qué esperas aprender.',
                'instructions' => 'Entrega en PDF a través del canal indicado por el docente.',
                'max_score' => 100,
                'due_at' => now()->addWeeks(2),
                'position' => 1,
                'is_published' => true,
            ],
        );

        // Evaluación en línea con preguntas.
        $quiz = Activity::updateOrCreate(
            ['subject_id' => $subject->id, 'title' => 'Cuestionario de introducción'],
            [
                'type' => ActivityType::Quiz->value,
                'description' => 'Comprueba lo aprendido en la primera unidad.',
                'max_score' => 100,
                'due_at' => now()->addWeeks(3),
                'position' => 2,
                'is_published' => true,
            ],
        );

        $evaluation = $quiz->evaluation()->firstOrCreate([]);
        $evaluation->update(['max_attempts' => 2, 'pass_score' => 60]);

        if ($evaluation->questions()->count() === 0) {
            $q1 = $evaluation->questions()->create([
                'type' => QuestionType::Single->value,
                'statement' => '¿Qué significa la sigla DSLE?',
                'score' => 2,
                'position' => 1,
            ]);
            $q1->options()->createMany([
                ['text' => 'DISKOVER Smart Learning Ecosystem', 'is_correct' => true, 'position' => 1],
                ['text' => 'Digital School Learning Environment', 'is_correct' => false, 'position' => 2],
                ['text' => 'Data Science Learning Engine', 'is_correct' => false, 'position' => 3],
            ]);

            $q2 = $evaluation->questions()->create([
                'type' => QuestionType::Boolean->value,
                'statement' => 'La plataforma permite experiencias inmersivas con Unity.',
                'score' => 1,
                'position' => 2,
            ]);
            $q2->options()->createMany([
                ['text' => 'Verdadero', 'is_correct' => true, 'position' => 1],
                ['text' => 'Falso', 'is_correct' => false, 'position' => 2],
            ]);

            $evaluation->questions()->create([
                'type' => QuestionType::Open->value,
                'statement' => 'Explica con tus palabras qué es la analítica educativa.',
                'score' => 3,
                'position' => 3,
            ]);
        }
    }
}
