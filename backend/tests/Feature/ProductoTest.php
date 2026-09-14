<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\EntradaStock;
use App\Models\Producto;
use App\Models\Ubicacion;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductoTest extends TestCase
{
    use RefreshDatabase;

    public function test_puede_darse_de_alta_un_producto(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $unidad = UnidadMedida::factory()->create();
        $categoria = Categoria::factory()->create();
        $ubicacion = Ubicacion::factory()->create();

        $response = $this->postJson('/api/productos', [
            'nombre' => 'Leche entera',
            'categoria_id' => $categoria->id,
            'unidad_medida_id' => $unidad->id,
            'ubicacion_por_defecto_id' => $ubicacion->id,
            'stock_minimo' => 2,
            'dias_caducidad_por_defecto' => 7,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.nombre', 'Leche entera');
        $this->assertDatabaseHas('productos', ['nombre' => 'Leche entera']);
    }

    public function test_registro_de_compra_con_caducidad_crea_entrada_de_stock(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $producto = Producto::factory()->create();
        $ubicacion = Ubicacion::factory()->create();

        $response = $this->postJson('/api/movimientos/compra', [
            'producto_id' => $producto->id,
            'ubicacion_id' => $ubicacion->id,
            'cantidad' => 6,
            'fecha_caducidad' => now()->addDays(10)->toDateString(),
            'precio_unitario' => 1.25,
            'usuario_atribuido_id' => $usuario->id,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.tipo', 'compra');
        $response->assertJsonPath('data.cantidad', 6);

        $entrada = EntradaStock::where('producto_id', $producto->id)->firstOrFail();
        $this->assertEquals(6, $entrada->cantidad_restante);
        $this->assertEquals(now()->addDays(10)->toDateString(), $entrada->fecha_caducidad->toDateString());
    }

    public function test_consulta_de_stock_agregado_suma_varias_entradas(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $producto = Producto::factory()->create();
        EntradaStock::factory()->create(['producto_id' => $producto->id, 'cantidad_restante' => 3]);
        EntradaStock::factory()->create(['producto_id' => $producto->id, 'cantidad_restante' => 2.5]);

        $response = $this->getJson("/api/productos/{$producto->id}/stock");

        $response->assertOk();
        $response->assertJsonPath('stock_total', 5.5);
        $response->assertJsonCount(2, 'entradas');
    }
}
