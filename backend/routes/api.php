<?php

// Las rutas de la API se definen en el proveedor RouteServiceProvider / bootstrap/app.php
// y se completan en routes/api.php mediante los distintos archivos de rutas del dominio.
require __DIR__.'/api/auth.php';
require __DIR__.'/api/inventario.php';
