<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AtribucionNoAutorizadaException;
use App\Exceptions\StockInsuficienteException;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarCompraRequest;
use App\Http\Requests\RegistrarConsumoRequest;
use App\Http\Requests\RegistrarCorreccionRequest;
use App\Http\Resources\MovimientoStockResource;
use App\Models\EntradaStock;
use App\Models\Producto;
use App\Repositories\Contracts\MovimientoStockRepositoryInterface;
use App\Services\MovimientoStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MovimientoStockController extends Controller
{
    public function __construct(
        private readonly MovimientoStockService $movimientoStockService,
        private readonly MovimientoStockRepositoryInterface $movimientoStockRepository,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return MovimientoStockResource::collection(
            $this->movimientoStockRepository->paginar(
                $request->integer('producto_id') ?: null,
                $request->string('tipo')->value() ?: null,
                $request->integer('por_pagina', 20),
            )
        );
    }

    public function compra(RegistrarCompraRequest $request): JsonResponse
    {
        $producto = Producto::findOrFail($request->validated('producto_id'));

        try {
            $movimiento = $this->movimientoStockService->registrarCompra(
                $producto,
                $request->validated(),
                $request->user(),
            );
        } catch (AtribucionNoAutorizadaException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return MovimientoStockResource::make(
            $movimiento->load(['producto', 'usuarioRegistrador', 'usuarioAtribuido'])
        )->response()->setStatusCode(201);
    }

    public function consumo(RegistrarConsumoRequest $request): JsonResponse
    {
        $producto = Producto::findOrFail($request->validated('producto_id'));

        try {
            $movimientos = $this->movimientoStockService->registrarConsumo(
                $producto,
                $request->validated(),
                $request->user(),
            );
        } catch (AtribucionNoAutorizadaException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (StockInsuficienteException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $movimientos->each->load(['producto', 'usuarioRegistrador', 'usuarioAtribuido']);

        return MovimientoStockResource::collection($movimientos)->response()->setStatusCode(201);
    }

    public function correccion(RegistrarCorreccionRequest $request): JsonResponse
    {
        $entrada = EntradaStock::findOrFail($request->validated('entrada_stock_id'));

        try {
            $movimiento = $this->movimientoStockService->registrarCorreccion(
                $entrada,
                $request->validated(),
                $request->user(),
            );
        } catch (AtribucionNoAutorizadaException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return MovimientoStockResource::make(
            $movimiento->load(['producto', 'usuarioRegistrador', 'usuarioAtribuido'])
        )->response()->setStatusCode(201);
    }
}
