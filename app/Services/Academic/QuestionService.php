<?php

namespace App\Services\Academic;

use App\Enums\QuestionType;
use App\Models\Evaluation;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

class QuestionService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Evaluation $evaluation, array $data): Question
    {
        return DB::transaction(function () use ($evaluation, $data): Question {
            $question = $evaluation->questions()->create([
                'type' => $data['type'],
                'statement' => $data['statement'],
                'score' => $data['score'] ?? 1,
                'position' => (int) $evaluation->questions()->max('position') + 1,
            ]);

            $this->syncOptions($question, $data);

            return $question;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Question $question, array $data): Question
    {
        return DB::transaction(function () use ($question, $data): Question {
            $question->update([
                'type' => $data['type'],
                'statement' => $data['statement'],
                'score' => $data['score'] ?? 1,
            ]);

            $question->options()->delete();
            $this->syncOptions($question, $data);

            return $question;
        });
    }

    public function delete(Question $question): void
    {
        $question->delete();
    }

    /**
     * Reconstruye las opciones de la pregunta a partir de los datos del
     * formulario. Para preguntas abiertas no se crean opciones.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncOptions(Question $question, array $data): void
    {
        $type = $question->type;

        if ($type === QuestionType::Open) {
            return;
        }

        if ($type === QuestionType::Boolean) {
            $correct = (string) ($data['boolean_answer'] ?? 'true');
            foreach (['true' => 'Verdadero', 'false' => 'Falso'] as $value => $label) {
                $question->options()->create([
                    'text' => $label,
                    'is_correct' => $value === $correct,
                    'position' => $value === 'true' ? 1 : 2,
                ]);
            }

            return;
        }

        $options = array_values($data['options'] ?? []);
        $correctIndexes = array_map('intval', (array) ($data['correct'] ?? []));

        foreach ($options as $index => $option) {
            $text = trim((string) ($option['text'] ?? ''));
            if ($text === '') {
                continue;
            }

            $question->options()->create([
                'text' => $text,
                'is_correct' => in_array($index, $correctIndexes, true),
                'position' => $index + 1,
            ]);
        }
    }
}
