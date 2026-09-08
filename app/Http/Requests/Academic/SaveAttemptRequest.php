<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Guardado y envío de un intento por parte del estudiante. Las respuestas
 * llegan como `answers[question_id] = opción|[opciones]|texto`; el servicio
 * las contrasta contra las preguntas reales de la evaluación.
 */
class SaveAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('attempt')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answers' => ['array'],
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function answers(): array
    {
        return (array) $this->input('answers', []);
    }
}
