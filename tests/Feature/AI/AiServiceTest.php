<?php

namespace Tests\Feature\AI;

use App\DTOs\AI\ChatMessage;
use App\DTOs\AI\ChatResponse;
use App\Enums\RoleSlug;
use App\Models\Activity;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\User;
use App\Services\AI\AiService;
use App\Services\AI\Contracts\AiProvider;
use App\Services\AI\Exceptions\AiException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class AiServiceTest extends TestCase
{
    use InteractsWithRoles;
    use RefreshDatabase;

    public function test_default_binding_is_the_stub_provider(): void
    {
        $this->assertFalse(app(AiService::class)->isRealProvider());
        $this->assertSame('stub', app(AiService::class)->providerName());
    }

    public function test_provider_failure_stores_a_failed_assistant_message(): void
    {
        $this->app->instance(AiProvider::class, new class implements AiProvider
        {
            public function chat(array $messages): ChatResponse
            {
                throw new AiException('boom');
            }

            public function name(): string
            {
                return 'openai';
            }

            public function model(): string
            {
                return 'test-model';
            }
        });

        $user = User::factory()->create();
        $conversation = app(AiService::class)->startConversation($user, 'hola');

        $assistant = $conversation->messages()->where('role', 'assistant')->firstOrFail();
        $this->assertTrue($assistant->failed);
        $this->assertStringContainsString('No he podido generar', $assistant->content);
    }

    public function test_prompt_includes_the_students_weak_subject_as_context(): void
    {
        $spy = new class implements AiProvider
        {
            /** @var array<int, ChatMessage> */
            public array $seen = [];

            public function chat(array $messages): ChatResponse
            {
                $this->seen = $messages;

                return new ChatResponse('ok', 'stub');
            }

            public function name(): string
            {
                return 'stub';
            }

            public function model(): string
            {
                return 'stub';
            }
        };
        $this->app->instance(AiProvider::class, $spy);

        $student = User::factory()->withRole(RoleSlug::Student->value)->create();
        $course = Course::factory()->create();
        $subject = Subject::factory()->for($course)->create(['name' => 'Estadística']);
        $activity = Activity::factory()->for($subject)->create(['max_score' => 100]);
        Enrollment::factory()->create(['course_id' => $course->id, 'student_id' => $student->id]);
        Grade::factory()->create(['activity_id' => $activity->id, 'student_id' => $student->id, 'score' => 30]);

        app(AiService::class)->startConversation($student, 'ayuda');

        $systemContent = collect($spy->seen)
            ->filter(fn ($m) => $m->role->value === 'system')
            ->map(fn ($m) => $m->content)
            ->implode("\n");

        $this->assertStringContainsString('CONTEXTO ACADÉMICO', $systemContent);
        $this->assertStringContainsString('Estadística', $systemContent);
    }
}
