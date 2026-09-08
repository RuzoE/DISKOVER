<?php

namespace App\Http\Requests\Academic;

use App\Models\Enrollment;
use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Enrollment::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'student_id' => 'estudiante',
        ];
    }
}
