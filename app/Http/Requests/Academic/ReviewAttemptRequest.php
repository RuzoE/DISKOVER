<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class ReviewAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('grade', $this->route('attempt')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scores' => ['array'],
            'scores.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
