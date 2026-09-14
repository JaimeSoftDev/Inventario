<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUnidadMedidaRequest;
use App\Http\Requests\UpdateUnidadMedidaRequest;
use App\Http\Resources\UnidadMedidaResource;
use App\Models\UnidadMedida;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class UnidadMedidaController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return UnidadMedidaResource::collection(UnidadMedida::orderBy('nombre')->get());
    }

    public function store(StoreUnidadMedidaRequest $request): JsonResponse
    {
        return UnidadMedidaResource::make(UnidadMedida::create($request->validated()))
            ->response()->setStatusCode(201);
    }

    public function show(UnidadMedida $unidad_medida): UnidadMedidaResource
    {
        return UnidadMedidaResource::make($unidad_medida);
    }

    public function update(UpdateUnidadMedidaRequest $request, UnidadMedida $unidad_medida): UnidadMedidaResource
    {
        $unidad_medida->update($request->validated());

        return UnidadMedidaResource::make($unidad_medida);
    }

    public function destroy(UnidadMedida $unidad_medida): Response
    {
        $unidad_medida->delete();

        return response()->noContent();
    }
}
