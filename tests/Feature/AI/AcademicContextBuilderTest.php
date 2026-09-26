<?php

namespace Tests\Feature\AI;

use App\Enums\RoleSlug;
use App\Models\Activity;
use App\Models\Content;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use App\Services\AI\AcademicContextBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class AcademicContextBuilderTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    public function test_student_context_lists_courses_and_weak_subjects(): void
    {
        $student = User::factory()->withRole(RoleSlug::Student->value)->create(['name' => 'Marta']);
        $course = Course::factory()->create(['name' => 'Programación I']);
        $subject = Subject::factory()->for($course)->create(['name' => 'Bucles']);
        $activity = Activity::factory()->for($subject)->create(['max_score' => 100]);
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);
        Grade::factory()->create(['activity_id' => $activity->id, 'student_id' => $student->id, 'score' => 25]);

        $context = app(AcademicContextBuilder::class)->for($student);

        $this->assertStringContainsString('CONTEXTO ACADÉMICO', $context);
        $this->assertStringContainsString('Estudiante: Marta', $context);
        $this->assertStringContainsString('Programación I', $context);
        $this->assertStringContainsString('A REFORZAR: Bucles', $context);
    }

    public function test_non_student_gets_a_generic_context(): void
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher->value)->create();

        $context = app(AcademicContextBuilder::class)->for($teacher);

        $this->assertStringContainsString('no es estudiante', $context);
    }

    public function test_student_without_courses_is_reported(): void
    {
        $student = User::factory()->withRole(RoleSlug::Student->value)->create();

        $this->assertStringContainsString('no está inscrito', app(AcademicContextBuilder::class)->for($student));
    }

    public function test_context_lists_own_pending_overdue_contents_and_feedback_only(): void
    {
        $student = User::factory()->withRole(RoleSlug::Student->value)->create(['name' => 'Marta Ruiz']);
        $classmate = User::factory()->withRole(RoleSlug::Student->value)->create(['name' => 'Pedro Díaz']);
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create(['name' => 'Álgebra']);
        foreach ([$student, $classmate] as $user) {
            Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $user->id]);
        }

        Activity::factory()->for($subject)->create(['title' => 'Taller de ecuaciones', 'due_at' => now()->addDays(2)]);
        Activity::factory()->for($subject)->create(['title' => 'Informe de matrices', 'due_at' => now()->subDays(2)]);
        Activity::factory()->for($subject)->create(['title' => 'Proyecto final', 'due_at' => now()->addDays(20)]);
        $quiz = Activity::factory()->quiz()->for($subject)->create(['title' => 'Cuestionario de fracciones']);
        $question = Question::factory()->withOptions()->create([
            'evaluation_id' => $quiz->evaluation->id,
            'statement' => 'Enunciado secreto de la pregunta',
        ]);
        Content::factory()->for($subject)->create(['title' => 'Lectura de polinomios']);

        $graded = Activity::factory()->for($subject)->create(['title' => 'Práctica 1']);
        Grade::factory()->create(['activity_id' => $graded->id, 'student_id' => $student->id, 'score' => 90, 'feedback' => 'Buen trabajo con los signos']);
        Grade::factory()->create(['activity_id' => $graded->id, 'student_id' => $classmate->id, 'score' => 40, 'feedback' => 'Comentario privado de Pedro']);

        $context = app(AcademicContextBuilder::class)->for($student);

        $this->assertStringContainsString('Estudiante: Marta.', $context);
        $this->assertStringNotContainsString('Ruiz', $context);
        $this->assertStringNotContainsString($student->email, $context);

        $this->assertStringContainsString('Informe de matrices — Álgebra — tarea', $context);
        $this->assertStringContainsString('— vencida.', $context);
        $this->assertStringContainsString('Cuestionario de fracciones — Álgebra — cuestionario', $context);
        $this->assertLessThan(strpos($context, 'Taller de ecuaciones'), strpos($context, 'Informe de matrices'));
        $this->assertMatchesRegularExpression('/VENCEN EN LOS PRÓXIMOS 7 DÍAS: [^\n]*Taller de ecuaciones/u', $context);
        $this->assertDoesNotMatchRegularExpression('/VENCEN EN LOS PRÓXIMOS 7 DÍAS: [^\n]*Proyecto final/u', $context);
        $this->assertStringContainsString('Lectura de polinomios', $context);
        $this->assertStringContainsString('Buen trabajo con los signos', $context);

        $this->assertStringNotContainsString('Pedro', $context);
        $this->assertStringNotContainsString('Enunciado secreto', $context);
        foreach ($question->options as $option) {
            $this->assertStringNotContainsString($option->text, $context);
        }
    }
}
