<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class ProductoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'categoria' => CategoriaResource::make($this->whenLoaded('categoria')),
            'unidad_medida' => UnidadMedidaResource::make($this->whenLoaded('unidadMedida')),
            'ubicacion_por_defecto' => UbicacionResource::make($this->whenLoaded('ubicacionPorDefecto')),
            'stock_minimo' => (float) $this->stock_minimo,
            'dias_caducidad_por_defecto' => $this->dias_caducidad_por_defecto,
            'precio_referencia' => $this->precio_referencia !== null ? (float) $this->precio_referencia : null,
            'notas' => $this->notas,
            // Cuando se precarga con withSum('entradasStock as stock_actual', 'cantidad_restante')
            // evitamos hacer una query extra por producto.
            'stock_actual' => (float) ($this->stock_actual ?? $this->stockActual()),
            // Agregados que el listado usa para el chip de urgencia y para
            // saber si el stock está repartido en varios lotes.
            'proxima_caducidad' => $this->whenNotNull(
                $this->proxima_caducidad
                    ? Carbon::parse($this->proxima_caducidad)->toDateString()
                    : null
            ),
            'lotes' => $this->whenNotNull($this->lotes),
        ];
    }
}
