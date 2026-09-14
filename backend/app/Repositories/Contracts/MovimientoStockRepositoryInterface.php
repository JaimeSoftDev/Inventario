<?php

namespace App\Repositories\Contracts;

use App\Models\MovimientoStock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MovimientoStockRepositoryInterface
{
    public function crear(array $datos): MovimientoStock;

    /**
     * Lista paginada de movimientos, opcionalmente filtrada por producto y/o tipo.
     */
    public function paginar(?int $productoId = null, ?string $tipo = null, int $porPagina = 20): LengthAwarePaginator;
}
