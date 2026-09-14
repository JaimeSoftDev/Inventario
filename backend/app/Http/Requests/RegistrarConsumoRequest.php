<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarConsumoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'producto_id' => ['required', 'exists:productos,id'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'usuario_atribuido_id' => ['required', 'exists:users,id'],
            'nota' => ['nullable', 'string'],
        ];
    }
}
