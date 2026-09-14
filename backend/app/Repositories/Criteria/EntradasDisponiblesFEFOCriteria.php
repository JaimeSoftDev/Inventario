<?php

namespace App\Repositories\Criteria;

use Illuminate\Database\Eloquent\Builder;

/**
 * Entradas de stock disponibles (cantidad_restante > 0) de un producto,
 * ordenadas según la política FEFO (first-expired-first-out) con desempate
 * "abierto primero": las entradas ya abiertas se consumen antes que las
 * cerradas, y entre entradas con el mismo estado de apertura se prioriza la
 * que caduca antes.
 */
class EntradasDisponiblesFEFOCriteria implements CriteriaInterface
{
    public function __construct(private readonly int $productoId) {}

    public function apply(Builder $query): Builder
    {
        return $query
            ->where('producto_id', $this->productoId)
            ->where('cantidad_restante', '>', 0)
            ->orderByDesc('abierto')
            ->orderByRaw('fecha_caducidad IS NULL, fecha_caducidad ASC')
            ->orderBy('fecha_compra');
    }
}
