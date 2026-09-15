<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

        $usuario = $this->buscar($identificador);

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

    /**
     * Busca por correo o por nombre, de lo más estricto a lo más tolerante.
     *
     * La comparación laxa se hace en PHP y no en SQL a propósito: `LOWER()`
     * no significa lo mismo en todos los motores —SQLite deja intactas las
     * vocales acentuadas y MySQL no—, y con eso "Álex" podía no encontrarse
     * a sí mismo según dónde estuviera desplegado. En un hogar la tabla de
     * usuarios tiene un puñado de filas, así que recorrerla no cuesta nada.
     */
    private function buscar(string $identificador): ?User
    {
        // Coincidencia exacta: es la normal y aprovecha el índice.
        $exacto = User::where('email', $identificador)
            ->orWhere('name', $identificador)
            ->first();

        if ($exacto) {
            return $exacto;
        }

        $buscado = mb_strtolower($identificador);
        $usuarios = User::all();

        // Sin distinguir mayúsculas: el teclado del móvil capitaliza solo.
        $porMayusculas = $usuarios->first(
            fn (User $u) => mb_strtolower($u->name) === $buscado
                || mb_strtolower($u->email) === $buscado
        );

        if ($porMayusculas) {
            return $porMayusculas;
        }

        // Y sin tildes: teclear "alvaro" en el móvil es más rápido que
        // buscar la Á, y en un hogar no hay ambigüedad posible.
        $sinTildes = Str::ascii($buscado);

        return $usuarios->first(
            fn (User $u) => Str::ascii(mb_strtolower($u->name)) === $sinTildes
        );
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
