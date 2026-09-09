<?php

namespace App\Http\Requests\Recommendations;

use App\Enums\RecommendationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RespondRecommendationRequest extends FormRequest
{
    /**
     * @var array<string, RecommendationStatus>
     */
    public const ACTIONS = [
        'accept' => RecommendationStatus::Accepted,
        'dismiss' => RecommendationStatus::Dismissed,
        'complete' => RecommendationStatus::Completed,
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('recommendation')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(array_keys(self::ACTIONS))],
        ];
    }

    public function targetStatus(): RecommendationStatus
    {
        return self::ACTIONS[$this->string('action')->value()];
    }
}
