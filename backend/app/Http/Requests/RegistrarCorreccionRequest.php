<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarCorreccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entrada_stock_id' => ['required', 'exists:entradas_stock,id'],
            'cantidad_nueva' => ['required', 'numeric', 'min:0'],
            'usuario_atribuido_id' => ['required', 'exists:users,id'],
            'nota' => ['nullable', 'string'],
        ];
    }
}
