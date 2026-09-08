<?php

namespace App\Http\Requests\Academic;

use App\Enums\AcademicStatus;
use App\Models\Subject;
use App\Rules\IsTeacher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Subject::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'alpha_dash', 'unique:subjects,code'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(AcademicStatus::class)],
            'position' => ['nullable', 'integer', 'min:0'],
            'teacher_id' => ['nullable', 'integer', new IsTeacher],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'código',
            'name' => 'nombre',
            'description' => 'descripción',
            'status' => 'estado',
            'position' => 'orden',
            'teacher_id' => 'docente',
        ];
    }
}
