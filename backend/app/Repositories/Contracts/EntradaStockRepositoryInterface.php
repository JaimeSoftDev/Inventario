<?php

namespace App\Repositories\Contracts;

use App\Models\EntradaStock;
use App\Repositories\Criteria\CriteriaInterface;
use Illuminate\Support\Collection;

interface EntradaStockRepositoryInterface
{
    /**
     * Devuelve las entradas de stock que cumplen el Criteria dado.
     *
     * @return Collection<int, EntradaStock>
     */
    public function porCriteria(CriteriaInterface $criteria): Collection;

    /**
     * Igual que porCriteria(), pero bloquea las filas devueltas
     * (SELECT ... FOR UPDATE) para poder descontar cantidad_restante de
     * forma segura dentro de una transacción concurrente.
     *
     * @return Collection<int, EntradaStock>
     */
    public function porCriteriaBloqueando(CriteriaInterface $criteria): Collection;

    /**
     * Bloquea la fila (SELECT ... FOR UPDATE) para actualizarla de forma
     * segura dentro de una transacción concurrente.
     */
    public function bloquearParaActualizar(int $id): EntradaStock;

    public function crear(array $datos): EntradaStock;

    public function guardar(EntradaStock $entradaStock): bool;

    public function paraProducto(int $productoId): Collection;
}
