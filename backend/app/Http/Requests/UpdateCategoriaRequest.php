<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'categoria_padre_id' => [
                'nullable',
                'exists:categorias,id',
                Rule::notIn([$this->route('categoria')?->id]),
            ],
        ];
    }
}
