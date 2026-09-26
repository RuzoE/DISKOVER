<?php

namespace Tests\Feature\AI;

use App\Enums\QuestionType;
use App\Enums\RoleSlug;
use App\Models\Activity;
use App\Models\AiConversation;
use App\Models\Attempt;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use App\Services\AI\AcademicIntegrityGuard;
use App\Services\AI\AiService;
use App\Services\AI\Contracts\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\Fakes\SpyAiProvider;
use Tests\TestCase;

class AcademicIntegrityTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    private const STATEMENT = '¿Cuál es la capital constitucional de Bolivia?';

    private SpyAiProvider $spy;

    private User $student;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->spy = new SpyAiProvider;
        $this->app->instance(AiProvider::class, $this->spy);

        $this->student = $this->roleUser(RoleSlug::Student, ['name' => 'Laura Martínez Gómez']);
        $course = Course::factory()->create();
        $this->subject = Subject::factory()->for($course)->create(['name' => 'Geografía']);
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $this->student->id]);
    }

    private function quiz(bool $open = true): Activity
    {
        $activity = Activity::factory()->quiz()->for($this->subject)->create([
            'title' => 'Cuestionario de capitales',
            'due_at' => $open ? now()->addDays(3) : now()->subDay(),
        ]);

        $question = Question::factory()->create([
            'evaluation_id' => $activity->evaluation->id,
            'type' => QuestionType::Single->value,
            'statement' => self::STATEMENT,
        ]);
        foreach (['Sucre', 'La Paz', 'Cochabamba'] as $i => $text) {
            $question->options()->create(['text' => $text, 'is_correct' => $i === 0, 'position' => $i + 1]);
        }

        return $activity;
    }

    private function send(User $user, string $message): string
    {
        return app(AiService::class)->startConversation($user, $message)
            ->messages()->where('role', 'assistant')->latest('id')->value('content');
    }

    public function test_a_normal_study_question_reaches_the_provider(): void
    {
        $this->quiz();

        $reply = $this->send($this->student, '¿Qué es un meridiano?');

        $this->assertTrue($this->spy->called());
        $this->assertSame('Respuesta de prueba', $reply);
        $this->assertStringNotContainsString('MODO GUIADO', $this->spy->lastSystemText());
        $this->assertDatabaseMissing('audit_logs', ['event' => 'ai.integrity.block']);
    }

    public function test_attempt_in_progress_pauses_the_assistant_without_calling_the_provider(): void
    {
        $quiz = $this->quiz();
        Attempt::factory()->create(['evaluation_id' => $quiz->evaluation->id, 'student_id' => $this->student->id]);

        $reply = $this->send($this->student, '¿Qué es un meridiano?');

        $this->assertFalse($this->spy->called());
        $this->assertSame(AcademicIntegrityGuard::REPLY_ATTEMPT_IN_PROGRESS, $reply);
        $this->assertDatabaseHas('audit_logs', ['event' => 'ai.integrity.block', 'user_id' => $this->student->id]);
    }

    public function test_copied_statement_of_an_open_question_is_blocked(): void
    {
        $this->quiz();

        $reply = $this->send($this->student, 'Ayuda: cual es la capital constitucional de BOLIVIA');

        $this->assertFalse($this->spy->called());
        $this->assertSame(AcademicIntegrityGuard::REPLY_QUESTION_COPIED, $reply);
        $this->assertDatabaseHas('audit_logs', ['event' => 'ai.integrity.block']);
    }

    public function test_two_options_of_an_open_question_are_blocked(): void
    {
        $this->quiz();

        $this->send($this->student, '¿Es Sucre o La Paz?');

        $this->assertFalse($this->spy->called());
    }

    public function test_a_closed_evaluation_does_not_block(): void
    {
        $this->quiz(open: false);

        $this->send($this->student, self::STATEMENT);

        $this->assertTrue($this->spy->called());
    }

    public function test_blocked_messages_are_never_resent_in_the_history(): void
    {
        $this->quiz();
        $conversation = AiConversation::factory()->for($this->student)->create();
        $ai = app(AiService::class);

        $ai->sendMessage($conversation, $this->student, self::STATEMENT);
        $ai->sendMessage($conversation, $this->student, '¿Qué es un meridiano?');

        $this->assertCount(1, $this->spy->calls);
        $this->assertStringNotContainsString('capital constitucional', $this->spy->lastPayloadText());
    }

    public function test_asking_to_do_the_homework_enables_guided_mode_and_is_audited(): void
    {
        $this->send($this->student, 'Hazme la tarea, por favor');

        $this->assertTrue($this->spy->called());
        $this->assertStringContainsString('MODO GUIADO', $this->spy->lastSystemText());
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'ai.integrity.guide',
            'user_id' => $this->student->id,
            'auditable_type' => (new AiConversation)->getMorphClass(),
        ]);
    }

    public function test_mentioning_a_pending_task_enables_guided_mode(): void
    {
        Activity::factory()->for($this->subject)->create(['title' => 'Ensayo sobre los ríos de América']);

        $this->send($this->student, 'Tengo dudas con el ensayo sobre los rios de america');

        $this->assertStringContainsString('MODO GUIADO', $this->spy->lastSystemText());
    }

    public function test_teachers_have_no_restrictions(): void
    {
        $this->quiz();
        $teacher = $this->roleUser(RoleSlug::Teacher);

        $this->send($teacher, 'Hazme la tarea: '.self::STATEMENT);

        $this->assertTrue($this->spy->called());
        $this->assertStringNotContainsString('MODO GUIADO', $this->spy->lastSystemText());
        $this->assertDatabaseMissing('audit_logs', ['event' => 'ai.integrity.guide']);
    }

    public function test_integrity_layer_can_be_disabled_by_configuration(): void
    {
        config(['dsle.ai.integrity' => false]);
        $this->quiz();

        $this->send($this->student, self::STATEMENT);

        $this->assertTrue($this->spy->called());
    }

    public function test_only_the_first_name_reaches_the_provider(): void
    {
        $this->send($this->student, '¿Cómo voy en mis asignaturas?');

        $payload = $this->spy->lastPayloadText();
        $this->assertStringContainsString('Estudiante: Laura.', $payload);
        $this->assertStringNotContainsString('Martínez', $payload);
        $this->assertStringNotContainsString($this->student->email, $payload);
    }

    public function test_normalization_removes_case_accents_and_punctuation(): void
    {
        $this->assertSame('resuelveme esto ya', AcademicIntegrityGuard::normalize('¡¡RESUÉLVEME esto, YA!!'));
    }
}
