<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4000'],
            'context_type' => ['nullable', Rule::in(['general', 'course', 'subject'])],
            'context_id' => ['nullable', 'integer'],
        ];
    }

    public function attributes(): array
    {
        return ['message' => 'mensaje'];
    }
}
