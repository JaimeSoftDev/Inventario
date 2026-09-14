<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UsuarioController extends Controller
{
    /**
     * Lista de usuarios del hogar, usada para poblar el selector
     * "Registrar a nombre de" en el frontend.
     */
    public function index(): AnonymousResourceCollection
    {
        return UsuarioResource::collection(User::orderBy('name')->get());
    }
}
