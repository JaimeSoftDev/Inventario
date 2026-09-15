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

    public function test_login_con_el_nombre_del_miembro_devuelve_token(): void
    {
        User::factory()->create([
            'name' => 'Jaime',
            'password' => Hash::make('secreto123'),
        ]);

        $response = $this->postJson('/api/login', [
            'identificador' => 'Jaime',
            'password' => 'secreto123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('usuario.name', 'Jaime');
    }

    public function test_el_nombre_no_distingue_mayusculas(): void
    {
        User::factory()->create([
            'name' => 'Jaime',
            'password' => Hash::make('secreto123'),
        ]);

        $this->postJson('/api/login', [
            'identificador' => 'jAiMe',
            'password' => 'secreto123',
        ])->assertOk();
    }

    public function test_el_identificador_admite_tambien_el_correo(): void
    {
        $usuario = User::factory()->create(['password' => Hash::make('secreto123')]);

        $this->postJson('/api/login', [
            'identificador' => $usuario->email,
            'password' => 'secreto123',
        ])->assertOk();
    }

    public function test_un_nombre_que_no_existe_no_entra(): void
    {
        User::factory()->create([
            'name' => 'Jaime',
            'password' => Hash::make('secreto123'),
        ]);

        $this->postJson('/api/login', [
            'identificador' => 'Antonio',
            'password' => 'secreto123',
        ])->assertStatus(422);
    }

    public function test_el_nombre_no_puede_repetirse_entre_miembros(): void
    {
        User::factory()->create(['name' => 'Jaime']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::factory()->create(['name' => 'Jaime']);
    }

    public function test_sigue_admitiendo_el_campo_email_de_las_pwa_instaladas(): void
    {
        $usuario = User::factory()->create(['password' => Hash::make('secreto123')]);

        $this->postJson('/api/login', [
            'email' => $usuario->email,
            'password' => 'secreto123',
        ])->assertOk();
    }

    public function test_sin_identificador_el_error_se_entiende(): void
    {
        $this->postJson('/api/login', ['password' => 'secreto123'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Indica tu usuario o tu correo.');
    }

    public function test_rutas_protegidas_requieren_autenticacion(): void
    {
        $this->getJson('/api/productos')->assertUnauthorized();
    }
}
