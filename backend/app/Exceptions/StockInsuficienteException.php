<?php

namespace App\Exceptions;

use Exception;

/**
 * Se lanza al intentar consumir más cantidad de un producto de la
 * disponible en stock. Debe traducirse a un HTTP 422 en el controller.
 */
class StockInsuficienteException extends Exception
{
    public function __construct(
        public readonly float $solicitado,
        public readonly float $disponible,
    ) {
        parent::__construct(sprintf(
            'Stock insuficiente: se solicitaron %s unidades pero solo hay %s disponibles.',
            rtrim(rtrim(number_format($solicitado, 3, '.', ''), '0'), '.'),
            rtrim(rtrim(number_format($disponible, 3, '.', ''), '0'), '.'),
        ));
    }
}
