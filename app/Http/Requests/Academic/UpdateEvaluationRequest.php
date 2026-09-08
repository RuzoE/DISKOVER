<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('activity')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'pass_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'time_limit_minutes' => 'tiempo límite',
            'max_attempts' => 'intentos permitidos',
            'shuffle_questions' => 'barajar preguntas',
            'pass_score' => 'nota de aprobado',
        ];
    }
}
