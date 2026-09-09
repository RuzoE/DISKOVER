<?php

namespace Database\Seeders;

use App\Enums\AcademicStatus;
use App\Enums\ActivityType;
use App\Enums\ContentType;
use App\Enums\EnrollmentStatus;
use App\Enums\GradeSource;
use App\Enums\ImmersiveProvider;
use App\Enums\LearningEventType;
use App\Enums\QuestionType;
use App\Models\Activity;
use App\Models\Content;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\ImmersiveExperience;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academic\AttemptService;
use App\Services\Analytics\LearningEventRecorder;
use App\Services\Recommendations\RecommendationService;
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
        $essay = Activity::updateOrCreate(
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

        // Actividad vencida sin entregar (para el motor de recomendaciones).
        Activity::updateOrCreate(
            ['subject_id' => $subject->id, 'title' => 'Tarea de repaso (semana 1)'],
            [
                'type' => ActivityType::Task->value,
                'description' => 'Resumen de la primera unidad.',
                'max_score' => 100,
                'due_at' => now()->subDays(3),
                'position' => 3,
                'is_published' => true,
            ],
        );

        // Actividad + experiencia inmersiva vinculada (Fase 9).
        $labActivity = Activity::updateOrCreate(
            ['subject_id' => $subject->id, 'title' => 'Laboratorio virtual: recorrido'],
            [
                'type' => ActivityType::Task->value,
                'description' => 'Completa el recorrido guiado del laboratorio en VR.',
                'max_score' => 100,
                'due_at' => now()->addWeeks(4),
                'position' => 4,
                'is_published' => true,
            ],
        );

        $experience = ImmersiveExperience::updateOrCreate(
            ['slug' => 'lab-virtual-recorrido'],
            [
                'title' => 'Laboratorio virtual — recorrido guiado',
                'description' => 'Recorrido inmersivo por el laboratorio de la asignatura.',
                'provider' => ImmersiveProvider::Simulator->value,
                'launch_url' => null,
                'config' => ['scene' => 'lab-01', 'difficulty' => 'normal'],
                'max_score' => 100,
                'status' => AcademicStatus::Active->value,
                'activity_id' => $labActivity->id,
                'created_by' => $teacher?->id,
            ],
        );
        $experience->subjects()->syncWithoutDetaching([$subject->id => ['is_required' => false]]);

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

        // Seguimiento de ejemplo: un contenido completado y una nota manual.
        if ($student) {
            $recorder = app(LearningEventRecorder::class);

            $recorder->record(
                $student,
                LearningEventType::Enrolled,
                "Inscripción en el curso «{$course->name}».",
                ['course_id' => $course->id],
            );

            $firstContent = $subject->contents()->orderBy('position')->first();
            if ($firstContent && ! $firstContent->isCompletedBy($student)) {
                $student->completedContents()->attach($firstContent->id, ['completed_at' => now()->subDays(2)]);
                $recorder->record(
                    $student,
                    LearningEventType::ContentCompleted,
                    "Contenido completado: «{$firstContent->title}».",
                    ['course_id' => $course->id, 'subject_id' => $subject->id],
                );
            }

            $grade = Grade::updateOrCreate(
                ['activity_id' => $essay->id, 'student_id' => $student->id],
                [
                    'score' => 82,
                    'feedback' => 'Buen planteamiento; amplía los ejemplos.',
                    'source' => GradeSource::Manual->value,
                    'graded_by' => $teacher?->id,
                    'graded_at' => now()->subDay(),
                ],
            );

            if ($grade->wasRecentlyCreated) {
                $recorder->record(
                    $student,
                    LearningEventType::ActivityGraded,
                    "Calificación registrada en «{$essay->title}»: 82/100.",
                    ['course_id' => $course->id, 'subject_id' => $subject->id, 'activity_id' => $essay->id],
                );
            }

            // Un intento del cuestionario, resuelto y revisado (datos para analítica).
            if ($quiz->evaluation && $quiz->evaluation->attempts()->where('student_id', $student->id)->doesntExist()) {
                $attempts = app(AttemptService::class);
                $attempt = $attempts->startOrResume($quiz->evaluation, $student);

                $questions = $quiz->evaluation->questions()->with('options')->get();
                $answers = [];
                // q1 (única) correcta, q2 (V/F) incorrecta, q3 (abierta) respondida.
                $answers[$questions[0]->id] = $questions[0]->options->firstWhere('is_correct', true)->id;
                $answers[$questions[1]->id] = $questions[1]->options->firstWhere('is_correct', false)->id;
                $answers[$questions[2]->id] = 'La analítica educativa transforma datos de aprendizaje en indicadores útiles.';

                $attempt = $attempts->submit($attempt, $answers);

                $openAnswer = $attempt->answers()
                    ->whereHas('question', fn ($q) => $q->where('type', QuestionType::Open->value))
                    ->first();

                if ($openAnswer) {
                    // Nota final del quiz = (q1: 2) + (q3: 1) = 3/6 = 50 % -> por debajo del aprobado.
                    $attempts->gradeOpenAnswers($attempt, [$openAnswer->id => 1]);
                }
            }

            // Genera las recomendaciones del estudiante demo.
            app(RecommendationService::class)->generateFor($student);
        }
    }
}
