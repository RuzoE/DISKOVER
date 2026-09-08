<?php

namespace App\Http\Requests\Academic;

use App\Enums\ActivityType;
use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [Activity::class, $this->route('subject')]) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ActivityType::class)],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'instructions' => ['nullable', 'string', 'max:10000'],
            'max_score' => ['required', 'numeric', 'min:1', 'max:9999'],
            'opens_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:opens_at'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'tipo', 'title' => 'título', 'description' => 'descripción',
            'instructions' => 'instrucciones', 'max_score' => 'puntuación máxima',
            'opens_at' => 'fecha de apertura', 'due_at' => 'fecha de entrega',
        ];
    }
}
