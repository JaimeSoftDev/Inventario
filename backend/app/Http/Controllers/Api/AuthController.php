<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Autentica por nombre de miembro **o** correo y emite un bearer token
     * (Sanctum personal access token) para el dispositivo/PWA.
     *
     * No se adivina cuál de los dos es: se busca por ambos a la vez. Así un
     * nombre con pinta de correo, o al revés, no deja a nadie fuera.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $identificador = $request->identificador();

        $usuario = User::where('email', $identificador)
            // El nombre se compara sin distinguir mayúsculas: en el móvil el
            // teclado capitaliza solo y nadie debería quedarse fuera por eso.
            ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($identificador)])
            ->first();

        if (! $usuario || ! Hash::check($datos['password'], $usuario->password)) {
            throw ValidationException::withMessages([
                $request->campoIdentificador() => ['Las credenciales proporcionadas no son correctas.'],
            ]);
        }

        $token = $usuario->createToken($datos['device_name'] ?? 'pwa')->plainTextToken;

        return response()->json([
            'token' => $token,
            'usuario' => UsuarioResource::make($usuario),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(status: 204);
    }

    public function me(Request $request): UsuarioResource
    {
        return UsuarioResource::make($request->user());
    }
}
