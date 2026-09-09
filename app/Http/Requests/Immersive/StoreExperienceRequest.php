<?php

namespace App\Http\Requests\Immersive;

use App\Enums\AcademicStatus;
use App\Enums\ImmersiveProvider;
use App\Models\ImmersiveExperience;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExperienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ImmersiveExperience::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:80', 'alpha_dash', $this->slugUniqueRule()],
            'description' => ['nullable', 'string', 'max:5000'],
            'provider' => ['required', Rule::enum(ImmersiveProvider::class)],
            'launch_url' => [
                'nullable', 'url', 'max:2048',
                Rule::requiredIf(fn () => $this->input('provider') !== ImmersiveProvider::Simulator->value),
            ],
            'config' => ['nullable', 'string', 'max:10000', $this->jsonRule()],
            'max_score' => ['required', 'numeric', 'min:1', 'max:9999'],
            'status' => ['required', Rule::enum(AcademicStatus::class)],
            'activity_id' => ['nullable', 'integer', 'exists:activities,id'],
            'subject_ids' => ['array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'required_subject_ids' => ['array'],
            'required_subject_ids.*' => ['integer'],
        ];
    }

    protected function slugUniqueRule(): mixed
    {
        return Rule::unique('immersive_experiences', 'slug');
    }

    protected function jsonRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (filled($value) && ! is_array(json_decode((string) $value, true))) {
                $fail('La configuración debe ser un objeto JSON válido.');
            }
        };
    }

    public function attributes(): array
    {
        return [
            'title' => 'título', 'slug' => 'identificador', 'provider' => 'proveedor',
            'launch_url' => 'URL de lanzamiento', 'config' => 'configuración',
            'max_score' => 'puntuación máxima', 'status' => 'estado',
            'activity_id' => 'actividad', 'subject_ids' => 'asignaturas',
        ];
    }
}
