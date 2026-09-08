<?php

namespace App\Http\Requests\Academic;

use App\Enums\ContentType;
use App\Models\Content;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El controlador ya ha resuelto la asignatura de la ruta y autoriza
        // con la policy de Content sobre esa asignatura.
        return $this->user()?->can('create', [Content::class, $this->route('subject')]) ?? false;
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
