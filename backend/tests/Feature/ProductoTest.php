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

    public function test_el_precio_de_referencia_se_guarda_y_se_devuelve(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/productos', [
            'nombre' => 'Aceite de oliva',
            'unidad_medida_id' => UnidadMedida::factory()->create()->id,
            'precio_referencia' => 8.45,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.precio_referencia', 8.45);

        $this->assertEquals(8.45, Producto::where('nombre', 'Aceite de oliva')->firstOrFail()->precio_referencia);
    }

    public function test_un_producto_puede_no_tener_precio(): void
    {
        Sanctum::actingAs(User::factory()->create());

        // Sin precio no es lo mismo que gratis: tiene que llegar como null
        // para que la interfaz pueda omitirlo en vez de pintar "0 €".
        $response = $this->postJson('/api/productos', [
            'nombre' => 'Queso curado',
            'unidad_medida_id' => UnidadMedida::factory()->create()->id,
            'precio_referencia' => null,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.precio_referencia', null);
    }

    public function test_un_precio_negativo_no_se_admite(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/productos', [
            'nombre' => 'Imposible',
            'unidad_medida_id' => UnidadMedida::factory()->create()->id,
            'precio_referencia' => -3,
        ])->assertStatus(422)->assertJsonValidationErrors('precio_referencia');
    }

    public function test_cada_lote_guarda_el_precio_de_su_compra(): void
    {
        $usuario = User::factory()->create();
        Sanctum::actingAs($usuario);

        $producto = Producto::factory()->create(['precio_referencia' => 0.95]);
        $ubicacion = Ubicacion::factory()->create();

        // Dos compras del mismo producto a precios distintos: es lo que
        // justifica guardarlo por lote y no solo en la ficha.
        foreach ([0.89, 0.99] as $precio) {
            $this->postJson('/api/movimientos/compra', [
                'producto_id' => $producto->id,
                'ubicacion_id' => $ubicacion->id,
                'cantidad' => 2,
                'precio_unitario' => $precio,
                'usuario_atribuido_id' => $usuario->id,
            ])->assertCreated();
        }

        $precios = EntradaStock::where('producto_id', $producto->id)
            ->orderBy('id')
            ->pluck('precio_unitario')
            ->map(fn ($p) => (float) $p)
            ->all();

        $this->assertSame([0.89, 0.99], $precios);

        // Y la ficha los expone para poder pintarlos.
        $this->getJson("/api/productos/{$producto->id}/stock")
            ->assertOk()
            ->assertJsonPath('entradas.0.precio_unitario', 0.89);
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

    public function test_el_listado_expone_la_proxima_caducidad_y_los_lotes_vivos(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $producto = Producto::factory()->create();

        // Solo cuentan las entradas con stock: la agotada no debe marcar la
        // caducidad del producto ni sumar como lote.
        EntradaStock::factory()->create([
            'producto_id' => $producto->id,
            'cantidad_restante' => 0,
            'fecha_caducidad' => now()->addDay()->toDateString(),
        ]);
        EntradaStock::factory()->create([
            'producto_id' => $producto->id,
            'cantidad_restante' => 4,
            'fecha_caducidad' => now()->addDays(9)->toDateString(),
        ]);
        EntradaStock::factory()->create([
            'producto_id' => $producto->id,
            'cantidad_restante' => 1,
            'fecha_caducidad' => now()->addDays(5)->toDateString(),
        ]);

        $response = $this->getJson('/api/productos');

        $response->assertOk();
        $response->assertJsonPath('data.0.proxima_caducidad', now()->addDays(5)->toDateString());
        $response->assertJsonPath('data.0.lotes', 2);
        $response->assertJsonPath('data.0.stock_actual', 5);
    }
}
