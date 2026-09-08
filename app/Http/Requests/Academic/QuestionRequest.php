<?php

namespace App\Http\Requests\Academic;

use App\Enums\QuestionType;
use App\Models\Activity;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Alta y edición de preguntas de una evaluación. Se usa tanto en creación
 * (ruta con {activity}) como en edición (ruta con {question}).
 */
class QuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->targetActivity()) ?? false;
    }

    private function targetActivity(): Activity
    {
        if ($this->route('activity')) {
            return $this->route('activity');
        }

        return $this->route('question')->evaluation->activity;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(QuestionType::class)],
            'statement' => ['required', 'string', 'max:5000'],
            'score' => ['required', 'numeric', 'min:0.25', 'max:1000'],
            'options' => ['array'],
            'options.*.text' => ['nullable', 'string', 'max:500'],
            'correct' => ['array'],
            'boolean_answer' => ['nullable', Rule::in(['true', 'false'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $type = QuestionType::tryFrom((string) $this->input('type'));
            if ($type === null) {
                return;
            }

            if (in_array($type, [QuestionType::Single, QuestionType::Multiple], true)) {
                $filled = collect($this->input('options', []))
                    ->filter(fn ($o) => filled($o['text'] ?? null))
                    ->keys()
                    ->all();

                if (count($filled) < 2) {
                    $v->errors()->add('options', 'Añade al menos dos opciones con texto.');
                }

                $correct = array_map('intval', (array) $this->input('correct', []));
                $correct = array_values(array_intersect($correct, $filled));

                if ($type === QuestionType::Single && count($correct) !== 1) {
                    $v->errors()->add('correct', 'Marca exactamente una opción correcta.');
                }

                if ($type === QuestionType::Multiple && count($correct) < 1) {
                    $v->errors()->add('correct', 'Marca al menos una opción correcta.');
                }
            }

            if ($type === QuestionType::Boolean && ! in_array($this->input('boolean_answer'), ['true', 'false'], true)) {
                $v->errors()->add('boolean_answer', 'Indica si la afirmación es verdadera o falsa.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'type' => 'tipo', 'statement' => 'enunciado', 'score' => 'puntuación',
            'options' => 'opciones', 'correct' => 'opciones correctas',
            'boolean_answer' => 'respuesta',
        ];
    }
}
