<?php

namespace App\Exceptions;

use Exception;

/**
 * Se lanza cuando un usuario intenta atribuir un movimiento de stock a otro
 * usuario sin tener permiso para ello. Debe traducirse a un HTTP 403 en el
 * controller. No se crea ningún movimiento cuando se lanza esta excepción.
 */
class AtribucionNoAutorizadaException extends Exception
{
    public function __construct()
    {
        parent::__construct('No tienes permiso para atribuir movimientos de stock a otro usuario.');
    }
}
