<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'producto_id' => ['required', 'exists:productos,id'],
            'ubicacion_id' => ['required', 'exists:ubicaciones,id'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'fecha_compra' => ['nullable', 'date'],
            'fecha_caducidad' => ['nullable', 'date'],
            'precio_unitario' => ['nullable', 'numeric', 'min:0'],
            'abierto' => ['nullable', 'boolean'],
            'usuario_atribuido_id' => ['required', 'exists:users,id'],
            'nota' => ['nullable', 'string'],
        ];
    }
}
