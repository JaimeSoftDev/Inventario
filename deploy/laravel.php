<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Puerta de entrada de la API en producción
|--------------------------------------------------------------------------
|
| En el hosting compartido de Hostinger no se puede cambiar el document
| root del dominio, que es lo que Laravel necesita (apuntar a public/).
| La solución es dejar la aplicación fuera de public_html —donde no es
| accesible desde el navegador, y con ella el .env— y publicar solo este
| fichero, que la arranca.
|
| Destino:  ~/domains/jaimesoftdev.com/public_html/laravel.php
| El .htaccess que lo acompaña envía aquí todo lo que empieza por /api.
|
| Si mueves el repositorio de sitio, esta es la única línea que cambia.
|
*/

$rutaAplicacion = '/home/u522908681/inventario/backend';

define('LARAVEL_START', microtime(true));

// Respeta el modo mantenimiento (php artisan down).
if (file_exists($mantenimiento = $rutaAplicacion.'/storage/framework/maintenance.php')) {
    require $mantenimiento;
}

require $rutaAplicacion.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $rutaAplicacion.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
