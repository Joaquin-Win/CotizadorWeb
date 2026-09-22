<?php

namespace App\Http\Requests\Cotizador;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEnvioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'origen' => ['required', 'string', 'max:255'],
            'destino' => ['required', 'string', 'max:255'],
            'peso_kg' => ['required', 'numeric', 'min:0.1'],
            'largo_cm' => ['required', 'numeric', 'min:1'],
            'ancho_cm' => ['required', 'numeric', 'min:1'],
            'alto_cm' => ['required', 'numeric', 'min:1'],
        ];
    }
}
