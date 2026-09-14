<?php

namespace App\Repositories\Eloquent;

use App\Models\EntradaStock;
use App\Repositories\Contracts\EntradaStockRepositoryInterface;
use App\Repositories\Criteria\CriteriaInterface;
use Illuminate\Support\Collection;

class EloquentEntradaStockRepository implements EntradaStockRepositoryInterface
{
    public function porCriteria(CriteriaInterface $criteria): Collection
    {
        return $criteria->apply(EntradaStock::query())->get();
    }

    public function porCriteriaBloqueando(CriteriaInterface $criteria): Collection
    {
        return $criteria->apply(EntradaStock::query())->lockForUpdate()->get();
    }

    public function bloquearParaActualizar(int $id): EntradaStock
    {
        return EntradaStock::query()->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    public function crear(array $datos): EntradaStock
    {
        return EntradaStock::create($datos);
    }

    public function guardar(EntradaStock $entradaStock): bool
    {
        return $entradaStock->save();
    }

    public function paraProducto(int $productoId): Collection
    {
        return EntradaStock::query()
            ->where('producto_id', $productoId)
            ->orderByDesc('fecha_compra')
            ->get();
    }
}
