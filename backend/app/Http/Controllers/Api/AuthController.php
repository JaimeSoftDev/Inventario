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
     * Autentica al usuario por email/password y emite un bearer token
     * (Sanctum personal access token) para el dispositivo/PWA.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $datos = $request->validated();

        $usuario = User::where('email', $datos['email'])->first();

        if (! $usuario || ! Hash::check($datos['password'], $usuario->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas no son correctas.'],
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
