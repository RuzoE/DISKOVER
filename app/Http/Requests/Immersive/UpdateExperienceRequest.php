<?php

namespace App\Http\Requests\Immersive;

use Illuminate\Validation\Rule;

class UpdateExperienceRequest extends StoreExperienceRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('experience')) ?? false;
    }

    protected function slugUniqueRule(): mixed
    {
        return Rule::unique('immersive_experiences', 'slug')->ignore($this->route('experience')->id);
    }
}
