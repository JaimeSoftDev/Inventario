<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'categoria_id' => ['nullable', 'exists:categorias,id'],
            'unidad_medida_id' => ['required', 'exists:unidades_medida,id'],
            'ubicacion_por_defecto_id' => ['nullable', 'exists:ubicaciones,id'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'dias_caducidad_por_defecto' => ['nullable', 'integer', 'min:0'],
            'precio_referencia' => ['nullable', 'numeric', 'min:0'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
