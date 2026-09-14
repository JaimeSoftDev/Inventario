<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Http\Resources\EntradaStockResource;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProductoController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ProductoResource::collection(
            Producto::with(['categoria', 'unidadMedida', 'ubicacionPorDefecto'])
                ->withSum('entradasStock as stock_actual', 'cantidad_restante')
                ->orderBy('nombre')
                ->get()
        );
    }

    public function store(StoreProductoRequest $request): JsonResponse
    {
        $producto = Producto::create($request->validated());

        return ProductoResource::make($producto->load(['categoria', 'unidadMedida', 'ubicacionPorDefecto']))
            ->response()->setStatusCode(201);
    }

    public function show(Producto $producto): ProductoResource
    {
        $producto->loadMissing(['categoria', 'unidadMedida', 'ubicacionPorDefecto'])
            ->loadSum('entradasStock as stock_actual', 'cantidad_restante');

        return ProductoResource::make($producto);
    }

    public function update(UpdateProductoRequest $request, Producto $producto): ProductoResource
    {
        $producto->update($request->validated());

        return ProductoResource::make($producto->fresh(['categoria', 'unidadMedida', 'ubicacionPorDefecto']));
    }

    public function destroy(Producto $producto): Response
    {
        $producto->delete();

        return response()->noContent();
    }

    /**
     * Stock agregado de un producto: total disponible y desglose por cada
     * entrada de stock viva (para ver caducidades y ubicaciones).
     */
    public function stock(Producto $producto): JsonResponse
    {
        $entradas = $producto->entradasStock()
            ->with('ubicacion')
            ->where('cantidad_restante', '>', 0)
            ->orderByDesc('abierto')
            ->orderBy('fecha_caducidad')
            ->get();

        return response()->json([
            'producto_id' => $producto->id,
            'stock_total' => (float) $entradas->sum('cantidad_restante'),
            'entradas' => EntradaStockResource::collection($entradas),
        ]);
    }
}
