<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_con_credenciales_correctas_devuelve_token(): void
    {
        $usuario = User::factory()->create(['password' => Hash::make('secreto123')]);

        $response = $this->postJson('/api/login', [
            'email' => $usuario->email,
            'password' => 'secreto123',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token', 'usuario' => ['id', 'name', 'email']]);
    }

    public function test_login_con_credenciales_incorrectas_falla(): void
    {
        $usuario = User::factory()->create(['password' => Hash::make('secreto123')]);

        $response = $this->postJson('/api/login', [
            'email' => $usuario->email,
            'password' => 'incorrecta',
        ]);

        $response->assertStatus(422);
    }

    public function test_rutas_protegidas_requieren_autenticacion(): void
    {
        $this->getJson('/api/productos')->assertUnauthorized();
    }
}
