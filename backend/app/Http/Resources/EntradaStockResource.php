<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntradaStockResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'ubicacion' => UbicacionResource::make($this->whenLoaded('ubicacion')),
            'cantidad_restante' => (float) $this->cantidad_restante,
            'fecha_compra' => $this->fecha_compra?->toDateString(),
            'fecha_caducidad' => $this->fecha_caducidad?->toDateString(),
            'precio_unitario' => $this->precio_unitario !== null ? (float) $this->precio_unitario : null,
            'abierto' => (bool) $this->abierto,
            'nota' => $this->nota,
        ];
    }
}
