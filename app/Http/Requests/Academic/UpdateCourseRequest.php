<?php

namespace App\Http\Requests\Academic;

use App\Enums\AcademicStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('course')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $courseId = $this->route('course')->id;

        return [
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('courses', 'code')->ignore($courseId)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(AcademicStatus::class)],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'código',
            'name' => 'nombre',
            'description' => 'descripción',
            'status' => 'estado',
            'starts_on' => 'fecha de inicio',
            'ends_on' => 'fecha de fin',
        ];
    }
}
