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
        // El mensaje lo lee una persona en el móvil (a veces al resolver un
        // conflicto de la cola offline), así que concuerda en número.
        parent::__construct(sprintf(
            'Stock insuficiente: %s %s pero solo %s %s.',
            $solicitado == 1 ? 'se pidió' : 'se pidieron',
            self::cantidad($solicitado, 'unidad', 'unidades'),
            $disponible == 1 ? 'queda' : 'quedan',
            self::cantidad($disponible, 'unidad', 'unidades'),
        ));
    }

    private static function cantidad(float $valor, string $singular, string $plural): string
    {
        $formateada = rtrim(rtrim(number_format($valor, 3, '.', ''), '0'), '.');

        return $formateada.' '.($valor == 1 ? $singular : $plural);
    }
}
