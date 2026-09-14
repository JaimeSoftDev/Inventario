<?php

use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\EntradaStockController;
use App\Http\Controllers\Api\MovimientoStockController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\UbicacionController;
use App\Http\Controllers\Api\UnidadMedidaController;
use App\Http\Controllers\Api\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/usuarios', [UsuarioController::class, 'index']);

    Route::apiResource('categorias', CategoriaController::class);
    Route::apiResource('ubicaciones', UbicacionController::class)
        ->parameters(['ubicaciones' => 'ubicacion']);
    Route::apiResource('unidades-medida', UnidadMedidaController::class)
        ->parameters(['unidades-medida' => 'unidad_medida']);

    Route::apiResource('productos', ProductoController::class);
    Route::get('/productos/{producto}/stock', [ProductoController::class, 'stock']);

    Route::apiResource('entradas-stock', EntradaStockController::class)
        ->parameters(['entradas-stock' => 'entrada_stock'])
        ->only(['index', 'show']);

    Route::get('/movimientos', [MovimientoStockController::class, 'index']);
    Route::post('/movimientos/compra', [MovimientoStockController::class, 'compra']);
    Route::post('/movimientos/consumo', [MovimientoStockController::class, 'consumo']);
    Route::post('/movimientos/correccion', [MovimientoStockController::class, 'correccion']);
});
