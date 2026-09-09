<?php

namespace App\Http\Requests\Immersive;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Resultado enviado por el cliente inmersivo (Unity) al terminar la sesión.
 * Se autoriza por el `launch_token` de la ruta, no por sesión de usuario.
 */
class CompleteSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'score' => ['required', 'numeric', 'min:0', 'max:100000'],
            'payload' => ['nullable', 'array'],
        ];
    }
}
