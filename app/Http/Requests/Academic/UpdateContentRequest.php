<?php

namespace App\Http\Requests\Academic;

use App\Enums\ContentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('content')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', Rule::enum(ContentType::class)],
            'body' => ['nullable', 'string', 'max:20000', 'required_if:type,'.ContentType::Text->value],
            'url' => [
                'nullable', 'url', 'max:2048',
                Rule::requiredIf(fn () => $this->input('type') && $this->input('type') !== ContentType::Text->value),
            ],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'type' => 'tipo',
            'body' => 'contenido',
            'url' => 'enlace',
            'position' => 'orden',
            'is_published' => 'publicado',
        ];
    }
}
