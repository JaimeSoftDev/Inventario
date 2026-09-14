<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovimientoStockResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'producto' => $this->whenLoaded('producto', fn () => [
                'id' => $this->producto->id,
                'nombre' => $this->producto->nombre,
            ]),
            'entrada_stock_id' => $this->entrada_stock_id,
            'tipo' => $this->tipo,
            'cantidad' => (float) $this->cantidad,
            'ubicacion_origen_id' => $this->ubicacion_origen_id,
            'ubicacion_destino_id' => $this->ubicacion_destino_id,
            'precio_unitario' => $this->precio_unitario !== null ? (float) $this->precio_unitario : null,
            'nota' => $this->nota,
            // No solo los IDs: se exponen también los nombres para no obligar
            // al frontend a resolverlos por separado.
            'registrado_por' => [
                'id' => $this->usuario_registrador_id,
                'nombre' => $this->whenLoaded('usuarioRegistrador', fn () => $this->usuarioRegistrador->name),
            ],
            'atribuido_a' => [
                'id' => $this->usuario_atribuido_id,
                'nombre' => $this->whenLoaded('usuarioAtribuido', fn () => $this->usuarioAtribuido->name),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
