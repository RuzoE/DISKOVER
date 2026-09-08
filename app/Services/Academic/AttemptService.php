<?php

namespace App\Services\Academic;

use App\Enums\AttemptStatus;
use App\Enums\QuestionType;
use App\Events\Academic\AttemptSubmitted;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Evaluation;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttemptService
{
    public function __construct(private readonly GradeService $grades) {}

    /**
     * Devuelve el intento en curso del estudiante o crea uno nuevo si quedan
     * intentos disponibles y la evaluación está abierta.
     */
    public function startOrResume(Evaluation $evaluation, User $student): Attempt
    {
        $inProgress = $evaluation->attempts()
            ->where('student_id', $student->id)
            ->where('status', AttemptStatus::InProgress->value)
            ->first();

        if ($inProgress) {
            return $inProgress;
        }

        $used = $evaluation->attempts()->where('student_id', $student->id)->count();

        if ($used >= $evaluation->max_attempts) {
            throw ValidationException::withMessages([
                'attempt' => 'Has agotado los intentos disponibles para esta evaluación.',
            ]);
        }

        return $evaluation->attempts()->create([
            'student_id' => $student->id,
            'number' => $used + 1,
            'status' => AttemptStatus::InProgress,
            'started_at' => now(),
        ]);
    }

    /**
     * Guarda las respuestas del formulario en el intento en curso (sin enviar).
     *
     * @param  array<int, mixed>  $answers  [question_id => opción(es) | texto]
     */
    public function saveAnswers(Attempt $attempt, array $answers): void
    {
        $questions = $attempt->evaluation->questions()->get()->keyBy('id');

        DB::transaction(function () use ($attempt, $answers, $questions): void {
            foreach ($questions as $questionId => $question) {
                $raw = $answers[$questionId] ?? null;

                $payload = $question->type === QuestionType::Open
                    ? ['text_answer' => is_string($raw) ? $raw : null, 'selected_option_ids' => null]
                    : ['selected_option_ids' => $this->normalizeOptionIds($raw), 'text_answer' => null];

                AttemptAnswer::updateOrCreate(
                    ['attempt_id' => $attempt->id, 'question_id' => $questionId],
                    $payload,
                );
            }
        });
    }

    /**
     * Envía el intento: corrige las preguntas cerradas, fija la puntuación y
     * consolida la calificación si no quedan preguntas abiertas.
     *
     * @param  array<int, mixed>  $answers
     */
    public function submit(Attempt $attempt, array $answers): Attempt
    {
        if (! $attempt->status->isOpen()) {
            return $attempt;
        }

        $this->saveAnswers($attempt, $answers);

        return DB::transaction(function () use ($attempt): Attempt {
            $questions = $attempt->evaluation->questions()->with('options')->get();
            $answersByQuestion = $attempt->answers()->get()->keyBy('question_id');

            $awarded = 0.0;
            $total = 0.0;
            $hasPendingOpen = false;

            foreach ($questions as $question) {
                $total += (float) $question->score;
                $answer = $answersByQuestion->get($question->id);

                if ($answer === null) {
                    continue;
                }

                if ($question->type === QuestionType::Open) {
                    $answer->update(['is_correct' => null, 'score_awarded' => null]);
                    $hasPendingOpen = true;

                    continue;
                }

                $correct = $this->isClosedAnswerCorrect($question, $answer->selected_option_ids ?? []);
                $answer->update([
                    'is_correct' => $correct,
                    'score_awarded' => $correct ? (float) $question->score : 0.0,
                ]);
                $awarded += $correct ? (float) $question->score : 0.0;
            }

            $attempt->update([
                'score' => round($awarded, 2),
                'max_score' => round($total, 2),
                'submitted_at' => now(),
                'status' => $hasPendingOpen ? AttemptStatus::Submitted : AttemptStatus::Graded,
            ]);

            if (! $hasPendingOpen) {
                $this->grades->syncFromBestAttempt($attempt->evaluation->activity, $attempt->student);
            }

            $attempt->refresh();
            AttemptSubmitted::dispatch($attempt);

            return $attempt;
        });
    }

    /**
     * Revisión del docente: asigna puntos a las respuestas abiertas y
     * consolida la calificación.
     *
     * @param  array<int, float>  $openScores  [answer_id => puntos]
     */
    public function gradeOpenAnswers(Attempt $attempt, array $openScores): Attempt
    {
        return DB::transaction(function () use ($attempt, $openScores): Attempt {
            $answers = $attempt->answers()->with('question')->get();
            $awarded = 0.0;

            foreach ($answers as $answer) {
                if ($answer->question->type === QuestionType::Open) {
                    $max = (float) $answer->question->score;
                    $points = min($max, max(0.0, (float) ($openScores[$answer->id] ?? 0)));
                    $answer->update([
                        'score_awarded' => round($points, 2),
                        'is_correct' => $points >= $max && $max > 0,
                    ]);
                }

                $awarded += (float) $answer->score_awarded;
            }

            $attempt->update([
                'score' => round($awarded, 2),
                'status' => AttemptStatus::Graded,
            ]);

            $this->grades->syncFromBestAttempt($attempt->evaluation->activity, $attempt->student);

            return $attempt->refresh();
        });
    }

    /**
     * @param  array<int, int>  $selected
     */
    private function isClosedAnswerCorrect(Question $question, array $selected): bool
    {
        $correct = $question->options->where('is_correct', true)->pluck('id')->map('intval')->sort()->values()->all();
        $given = collect($selected)->map('intval')->unique()->sort()->values()->all();

        return $correct === $given && $correct !== [];
    }

    /**
     * @return array<int, int>
     */
    private function normalizeOptionIds(mixed $raw): array
    {
        return collect(is_array($raw) ? $raw : [$raw])
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();
    }
}
