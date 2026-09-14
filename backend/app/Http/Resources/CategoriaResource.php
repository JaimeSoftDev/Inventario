<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoriaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'categoria_padre_id' => $this->categoria_padre_id,
            'categoria_padre' => CategoriaResource::make($this->whenLoaded('categoriaPadre')),
        ];
    }
}
