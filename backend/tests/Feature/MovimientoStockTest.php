<?php

namespace Tests\Feature;

use App\Models\EntradaStock;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MovimientoStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumo_de_una_sola_entrada(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $producto = Producto::factory()->create();
        $entrada = EntradaStock::factory()->create([
            'producto_id' => $producto->id,
            'cantidad_restante' => 10,
        ]);

        $response = $this->postJson('/api/movimientos/consumo', [
            'producto_id' => $producto->id,
            'cantidad' => 4,
            'usuario_atribuido_id' => $usuario->id,
        ]);

        $response->assertCreated();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.cantidad', 4);
        $response->assertJsonPath('data.0.entrada_stock_id', $entrada->id);

        $this->assertEquals(6, $entrada->fresh()->cantidad_restante);
    }

    public function test_consumo_repartido_entre_varias_entradas_respetando_fefo(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $producto = Producto::factory()->create();

        // La que caduca antes debe consumirse primero, aunque se haya
        // comprado después.
        $caducaAntes = EntradaStock::factory()->create([
            'producto_id' => $producto->id,
            'cantidad_restante' => 3,
            'fecha_compra' => now()->subDay(),
            'fecha_caducidad' => now()->addDays(2),
            'abierto' => false,
        ]);

        $caducaDespues = EntradaStock::factory()->create([
            'producto_id' => $producto->id,
            'cantidad_restante' => 5,
            'fecha_compra' => now()->subDays(5),
            'fecha_caducidad' => now()->addDays(10),
            'abierto' => false,
        ]);

        // Se piden 6 unidades: debe agotar la que caduca antes (3) y tomar
        // 3 más de la siguiente.
        $response = $this->postJson('/api/movimientos/consumo', [
            'producto_id' => $producto->id,
            'cantidad' => 6,
            'usuario_atribuido_id' => $usuario->id,
        ]);

        $response->assertCreated();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.entrada_stock_id', $caducaAntes->id);
        $response->assertJsonPath('data.0.cantidad', 3);
        $response->assertJsonPath('data.1.entrada_stock_id', $caducaDespues->id);
        $response->assertJsonPath('data.1.cantidad', 3);

        $this->assertEquals(0, $caducaAntes->fresh()->cantidad_restante);
        $this->assertEquals(2, $caducaDespues->fresh()->cantidad_restante);
    }

    public function test_entrada_abierta_se_consume_antes_aunque_caduque_mas_tarde(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $producto = Producto::factory()->create();

        $cerradaCaducaAntes = EntradaStock::factory()->create([
            'producto_id' => $producto->id,
            'cantidad_restante' => 5,
            'fecha_caducidad' => now()->addDay(),
            'abierto' => false,
        ]);

        $abiertaCaducaDespues = EntradaStock::factory()->create([
            'producto_id' => $producto->id,
            'cantidad_restante' => 5,
            'fecha_caducidad' => now()->addDays(30),
            'abierto' => true,
        ]);

        $response = $this->postJson('/api/movimientos/consumo', [
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'usuario_atribuido_id' => $usuario->id,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.0.entrada_stock_id', $abiertaCaducaDespues->id);

        $this->assertEquals(3, $abiertaCaducaDespues->fresh()->cantidad_restante);
        $this->assertEquals(5, $cerradaCaducaAntes->fresh()->cantidad_restante);
    }

    public function test_un_usuario_puede_atribuirse_un_consumo_a_si_mismo(): void
    {
        $usuario = User::factory()->create(); // sin permiso de atribución a otros
        Sanctum::actingAs($usuario);

        $producto = Producto::factory()->create();
        EntradaStock::factory()->create(['producto_id' => $producto->id, 'cantidad_restante' => 10]);

        $response = $this->postJson('/api/movimientos/consumo', [
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'usuario_atribuido_id' => $usuario->id,
        ]);

        $response->assertCreated();
    }

    public function test_atribuir_a_otro_usuario_con_permiso_funciona(): void
    {
        $registrador = User::factory()->conPermisoDeAtribucion()->create();
        $otroUsuario = User::factory()->create();
        Sanctum::actingAs($registrador);

        $producto = Producto::factory()->create();
        EntradaStock::factory()->create(['producto_id' => $producto->id, 'cantidad_restante' => 10]);

        $response = $this->postJson('/api/movimientos/consumo', [
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'usuario_atribuido_id' => $otroUsuario->id,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.0.atribuido_a.id', $otroUsuario->id);
        $response->assertJsonPath('data.0.registrado_por.id', $registrador->id);
    }

    public function test_atribuir_a_otro_usuario_sin_permiso_devuelve_403(): void
    {
        $registrador = User::factory()->create(); // sin permiso
        $otroUsuario = User::factory()->create();
        Sanctum::actingAs($registrador);

        $producto = Producto::factory()->create();
        EntradaStock::factory()->create(['producto_id' => $producto->id, 'cantidad_restante' => 10]);

        $response = $this->postJson('/api/movimientos/consumo', [
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'usuario_atribuido_id' => $otroUsuario->id,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('movimientos_stock', 0);
        $this->assertEquals(10, EntradaStock::first()->cantidad_restante);
    }

    public function test_consumir_mas_de_lo_disponible_devuelve_422_y_no_modifica_stock(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $producto = Producto::factory()->create();
        $entrada = EntradaStock::factory()->create(['producto_id' => $producto->id, 'cantidad_restante' => 5]);

        $response = $this->postJson('/api/movimientos/consumo', [
            'producto_id' => $producto->id,
            'cantidad' => 10,
            'usuario_atribuido_id' => $usuario->id,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('movimientos_stock', 0);
        $this->assertEquals(5, $entrada->fresh()->cantidad_restante);
    }

    public function test_dos_consumos_secuenciales_no_descuadran_el_stock(): void
    {
        // Simula dos usuarios consumiendo el mismo producto: cada consumo se
        // procesa dentro de su propia transacción con lockForUpdate() sobre
        // las entradas de stock, por lo que la suma final debe cuadrar
        // exactamente sin condiciones de carrera.
        $usuario1 = User::factory()->create();
        $usuario2 = User::factory()->create();

        $producto = Producto::factory()->create();
        EntradaStock::factory()->create(['producto_id' => $producto->id, 'cantidad_restante' => 10]);

        Sanctum::actingAs($usuario1);
        $this->postJson('/api/movimientos/consumo', [
            'producto_id' => $producto->id,
            'cantidad' => 4,
            'usuario_atribuido_id' => $usuario1->id,
        ])->assertCreated();

        Sanctum::actingAs($usuario2);
        $this->postJson('/api/movimientos/consumo', [
            'producto_id' => $producto->id,
            'cantidad' => 6,
            'usuario_atribuido_id' => $usuario2->id,
        ])->assertCreated();

        $this->assertEquals(0, $producto->entradasStock()->sum('cantidad_restante'));
        $this->assertEquals(2, DB::table('movimientos_stock')->where('tipo', 'consumo')->count());
    }
}
