<?php

namespace App\Http\Requests\Academic;

use App\Models\Grade;
use Illuminate\Foundation\Http\FormRequest;

class StoreGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage', [Grade::class, $this->route('activity')]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $max = (float) $this->route('activity')->max_score;

        return [
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'score' => ['required', 'numeric', 'min:0', 'max:'.$max],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'student_id' => 'estudiante',
            'score' => 'calificación',
            'feedback' => 'retroalimentación',
        ];
    }
}
