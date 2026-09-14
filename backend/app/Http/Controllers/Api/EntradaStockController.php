<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EntradaStockResource;
use App\Models\EntradaStock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EntradaStockController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $entradas = EntradaStock::with('ubicacion')
            ->when($request->integer('producto_id'), fn ($query, $productoId) => $query->where('producto_id', $productoId))
            ->orderByDesc('fecha_compra')
            ->paginate($request->integer('por_pagina', 20));

        return EntradaStockResource::collection($entradas);
    }

    public function show(EntradaStock $entrada_stock): EntradaStockResource
    {
        return EntradaStockResource::make($entrada_stock->load('ubicacion'));
    }
}
