<?php

namespace App\Repositories\Criteria;

use Illuminate\Database\Eloquent\Builder;

/**
 * Un Criteria encapsula una porción de query reutilizable (filtros, orden)
 * que se puede aplicar sobre el Builder de un repositorio, evitando que esa
 * lógica quede embebida directamente en el repositorio.
 */
interface CriteriaInterface
{
    public function apply(Builder $query): Builder;
}
