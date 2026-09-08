<?php

namespace App\Http\Requests\Academic;

use App\Enums\EnrollmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('enrollment')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(EnrollmentStatus::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'estado',
        ];
    }
}
