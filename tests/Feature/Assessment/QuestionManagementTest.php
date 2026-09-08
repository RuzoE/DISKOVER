<?php

namespace Tests\Feature\Assessment;

use App\Enums\QuestionType;
use App\Enums\RoleSlug;
use App\Models\Activity;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function quiz(): array
    {
        $teacher = User::factory()->withRole(RoleSlug::Teacher->value)->create();
        $subject = Subject::factory()->create(['teacher_id' => $teacher->id]);
        $activity = Activity::factory()->for($subject)->quiz()->create();

        return [$teacher, $activity];
    }

    public function test_teacher_adds_a_single_choice_question(): void
    {
        [$teacher, $activity] = $this->quiz();

        $this->actingAs($teacher)
            ->post(route('teacher.activities.questions.store', $activity), [
                'type' => QuestionType::Single->value,
                'statement' => '¿2 + 2?',
                'score' => 1,
                'options' => [['text' => '3'], ['text' => '4'], ['text' => '5']],
                'correct' => [1],
            ])
            ->assertRedirect();

        $question = $activity->evaluation->questions()->firstOrFail();
        $this->assertCount(3, $question->options);
        $this->assertCount(1, $question->correctOptionIds());
        $this->assertSame('4', $question->options->firstWhere('is_correct', true)->text);
    }

    public function test_single_choice_requires_exactly_one_correct_option(): void
    {
        [$teacher, $activity] = $this->quiz();

        $this->actingAs($teacher)
            ->post(route('teacher.activities.questions.store', $activity), [
                'type' => QuestionType::Single->value,
                'statement' => 'x',
                'score' => 1,
                'options' => [['text' => 'a'], ['text' => 'b']],
                'correct' => [0, 1],
            ])
            ->assertSessionHasErrors('correct');
    }

    public function test_choice_question_requires_at_least_two_options(): void
    {
        [$teacher, $activity] = $this->quiz();

        $this->actingAs($teacher)
            ->post(route('teacher.activities.questions.store', $activity), [
                'type' => QuestionType::Single->value,
                'statement' => 'x',
                'score' => 1,
                'options' => [['text' => 'sola']],
                'correct' => [0],
            ])
            ->assertSessionHasErrors('options');
    }

    public function test_boolean_question_stores_two_options(): void
    {
        [$teacher, $activity] = $this->quiz();

        $this->actingAs($teacher)
            ->post(route('teacher.activities.questions.store', $activity), [
                'type' => QuestionType::Boolean->value,
                'statement' => 'El cielo es azul.',
                'score' => 1,
                'boolean_answer' => 'true',
            ])
            ->assertRedirect();

        $question = $activity->evaluation->questions()->firstOrFail();
        $this->assertCount(2, $question->options);
        $this->assertTrue($question->options->firstWhere('text', 'Verdadero')->is_correct);
    }

    public function test_open_question_needs_no_options(): void
    {
        [$teacher, $activity] = $this->quiz();

        $this->actingAs($teacher)
            ->post(route('teacher.activities.questions.store', $activity), [
                'type' => QuestionType::Open->value,
                'statement' => 'Explica el teorema.',
                'score' => 5,
            ])
            ->assertRedirect();

        $question = $activity->evaluation->questions()->firstOrFail();
        $this->assertCount(0, $question->options);
    }
}
