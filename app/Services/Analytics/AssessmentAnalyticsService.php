<?php

namespace App\Services\Analytics;

use App\Enums\AttemptStatus;
use App\Enums\QuestionType;
use App\Models\AttemptAnswer;
use App\Models\Evaluation;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Support\Collection;

/**
 * Análisis a nivel de evaluación y de pregunta: tasa de acierto, puntuación
 * media e identificación de las preguntas más difíciles.
 */
class AssessmentAnalyticsService
{
    private function weakRate(): float
    {
        return (float) config('dsle.analytics.weak_question_rate', 0.5);
    }

    /**
     * Desglose de una evaluación pregunta por pregunta, sobre los intentos
     * enviados o calificados.
     *
     * @return array<int, array{
     *   question: Question, answered: int, correct: int,
     *   success_rate: float|null, average_score: float|null
     * }>
     */
    public function evaluationBreakdown(Evaluation $evaluation): array
    {
        $questions = $evaluation->questions()->with('options')->get();

        $answers = AttemptAnswer::query()
            ->whereIn('question_id', $questions->pluck('id'))
            ->whereHas('attempt', fn ($q) => $q->whereIn('status', [
                AttemptStatus::Submitted->value, AttemptStatus::Graded->value,
            ]))
            ->get()
            ->groupBy('question_id');

        return $questions->map(function (Question $question) use ($answers) {
            /** @var Collection<int, AttemptAnswer> $qa */
            $qa = $answers->get($question->id, collect());
            $answered = $qa->count();
            $correct = $qa->where('is_correct', true)->count();
            $scores = $qa->map(fn ($a) => $a->score_awarded)->filter(fn ($v) => $v !== null);
            $avgScore = $scores->isEmpty() ? null : round($scores->avg(), 2);
            $maxScore = (float) $question->score;

            // Cerradas: proporción de aciertos. Abiertas: proporción de puntos.
            $mastery = match (true) {
                $answered === 0 => null,
                $question->type === QuestionType::Open => $avgScore !== null && $maxScore > 0
                    ? round($avgScore / $maxScore, 2) : null,
                default => round($correct / $answered, 2),
            };

            return [
                'question' => $question,
                'answered' => $answered,
                'correct' => $correct,
                'success_rate' => $mastery,
                'average_score' => $avgScore,
            ];
        })->all();
    }

    /**
     * Preguntas más difíciles de todas las evaluaciones de una asignatura.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function difficultQuestions(Subject $subject, int $limit = 10): Collection
    {
        $rows = collect();

        $evaluations = Evaluation::query()
            ->whereHas('activity', fn ($q) => $q->where('subject_id', $subject->id))
            ->with('activity')
            ->get();

        foreach ($evaluations as $evaluation) {
            foreach ($this->evaluationBreakdown($evaluation) as $item) {
                if ($item['answered'] >= 1 && $item['success_rate'] !== null && $item['success_rate'] < $this->weakRate()) {
                    $rows->push([
                        'statement' => $item['question']->statement,
                        'activity' => $evaluation->activity->title,
                        'success_rate' => $item['success_rate'],
                        'answered' => $item['answered'],
                    ]);
                }
            }
        }

        return $rows->sortBy('success_rate')->take($limit)->values();
    }
}
