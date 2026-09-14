<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUbicacionRequest;
use App\Http\Requests\UpdateUbicacionRequest;
use App\Http\Resources\UbicacionResource;
use App\Models\Ubicacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class UbicacionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return UbicacionResource::collection(Ubicacion::orderBy('nombre')->get());
    }

    public function store(StoreUbicacionRequest $request): JsonResponse
    {
        return UbicacionResource::make(Ubicacion::create($request->validated()))
            ->response()->setStatusCode(201);
    }

    public function show(Ubicacion $ubicacion): UbicacionResource
    {
        return UbicacionResource::make($ubicacion);
    }

    public function update(UpdateUbicacionRequest $request, Ubicacion $ubicacion): UbicacionResource
    {
        $ubicacion->update($request->validated());

        return UbicacionResource::make($ubicacion);
    }

    public function destroy(Ubicacion $ubicacion): Response
    {
        $ubicacion->delete();

        return response()->noContent();
    }

    // Nota: el parámetro de ruta se llama "ubicacion" (ver routes/api/inventario.php),
    // ya que Str::singular('ubicaciones') no produce el singular correcto en español.
}
