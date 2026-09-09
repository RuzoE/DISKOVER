<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('conversation')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4000'],
        ];
    }

    public function attributes(): array
    {
        return ['message' => 'mensaje'];
    }
}
