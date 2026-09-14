<?php

namespace App\Repositories\Eloquent;

use App\Models\MovimientoStock;
use App\Repositories\Contracts\MovimientoStockRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentMovimientoStockRepository implements MovimientoStockRepositoryInterface
{
    public function crear(array $datos): MovimientoStock
    {
        return MovimientoStock::create($datos);
    }

    public function paginar(?int $productoId = null, ?string $tipo = null, int $porPagina = 20): LengthAwarePaginator
    {
        return MovimientoStock::query()
            ->with(['producto', 'entradaStock', 'usuarioRegistrador', 'usuarioAtribuido'])
            ->when($productoId, fn ($query) => $query->where('producto_id', $productoId))
            ->when($tipo, fn ($query) => $query->where('tipo', $tipo))
            ->orderByDesc('created_at')
            ->paginate($porPagina);
    }
}
