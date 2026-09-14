<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\EntradaStock;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\Ubicacion;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Hogar de ejemplo con datos que ejercitan todos los estados de la interfaz:
 * caducidad inminente, stock bajo, producto repartido en varios lotes,
 * consumos atribuidos a terceros y compras de distintas personas.
 *
 *   php artisan migrate:fresh --seed --seeder=Database\\Seeders\\DemoSeeder
 */
class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $ana = User::create([
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => Hash::make('password'),
            'puede_atribuir_a_otros' => true,
        ]);

        $luis = User::create([
            'name' => 'Luis',
            'email' => 'luis@example.com',
            'password' => Hash::make('password'),
        ]);

        $marta = User::create([
            'name' => 'Marta',
            'email' => 'marta@example.com',
            'password' => Hash::make('password'),
        ]);

        $iker = User::create([
            'name' => 'Iker',
            'email' => 'iker@example.com',
            'password' => Hash::make('password'),
        ]);

        $ubicaciones = collect(['Nevera', 'Despensa', 'Congelador', 'Baño'])
            ->mapWithKeys(fn (string $nombre) => [$nombre => Ubicacion::create(['nombre' => $nombre])]);

        $unidades = collect([
            ['nombre' => 'Unidad', 'abreviatura' => 'uds'],
            ['nombre' => 'Brik', 'abreviatura' => 'briks'],
            ['nombre' => 'Gramo', 'abreviatura' => 'g'],
            ['nombre' => 'Litro', 'abreviatura' => 'L'],
            ['nombre' => 'Paquete', 'abreviatura' => 'packs'],
        ])->mapWithKeys(fn (array $datos) => [$datos['abreviatura'] => UnidadMedida::create($datos)]);

        $categorias = collect(['Lácteos', 'Limpieza', 'Despensa', 'Huevos'])
            ->mapWithKeys(fn (string $nombre) => [$nombre => Categoria::create(['nombre' => $nombre])]);

        // --- Productos ---------------------------------------------------
        $yogur = Producto::create([
            'nombre' => 'Yogur natural',
            'categoria_id' => $categorias['Lácteos']->id,
            'unidad_medida_id' => $unidades['uds']->id,
            'ubicacion_por_defecto_id' => $ubicaciones['Nevera']->id,
            'stock_minimo' => 4,
            'dias_caducidad_por_defecto' => 14,
        ]);

        $leche = Producto::create([
            'nombre' => 'Leche entera',
            'descripcion' => 'brik 1 L',
            'categoria_id' => $categorias['Lácteos']->id,
            'unidad_medida_id' => $unidades['briks']->id,
            'ubicacion_por_defecto_id' => $ubicaciones['Nevera']->id,
            'stock_minimo' => 4,
            'dias_caducidad_por_defecto' => 20,
        ]);

        $detergente = Producto::create([
            'nombre' => 'Detergente',
            'categoria_id' => $categorias['Limpieza']->id,
            'unidad_medida_id' => $unidades['L']->id,
            'ubicacion_por_defecto_id' => $ubicaciones['Baño']->id,
            'stock_minimo' => 2,
        ]);

        $queso = Producto::create([
            'nombre' => 'Queso curado',
            'categoria_id' => $categorias['Lácteos']->id,
            'unidad_medida_id' => $unidades['g']->id,
            'ubicacion_por_defecto_id' => $ubicaciones['Nevera']->id,
            'stock_minimo' => 100,
            'dias_caducidad_por_defecto' => 30,
        ]);

        $huevos = Producto::create([
            'nombre' => 'Huevos',
            'categoria_id' => $categorias['Huevos']->id,
            'unidad_medida_id' => $unidades['uds']->id,
            'ubicacion_por_defecto_id' => $ubicaciones['Nevera']->id,
            'stock_minimo' => 6,
            'dias_caducidad_por_defecto' => 21,
        ]);

        $arroz = Producto::create([
            'nombre' => 'Arroz redondo',
            'categoria_id' => $categorias['Despensa']->id,
            'unidad_medida_id' => $unidades['packs']->id,
            'ubicacion_por_defecto_id' => $ubicaciones['Despensa']->id,
            'stock_minimo' => 1,
        ]);

        $garbanzos = Producto::create([
            'nombre' => 'Garbanzos',
            'categoria_id' => $categorias['Despensa']->id,
            'unidad_medida_id' => $unidades['packs']->id,
            'ubicacion_por_defecto_id' => $ubicaciones['Despensa']->id,
            'stock_minimo' => 1,
        ]);

        // --- Lotes con su compra correspondiente -------------------------
        $this->lote($yogur, $ubicaciones['Nevera'], 8, $ana, dias: 2, comprado: 4);
        // Leche repartida en dos lotes: uno urgente y otro holgado. Es el
        // caso que justifica FEFO y el desglose por lotes de la ficha.
        $this->lote($leche, $ubicaciones['Nevera'], 1, $luis, dias: 2, comprado: 5);
        $this->lote($leche, $ubicaciones['Nevera'], 1, $ana, dias: 20, comprado: 2);
        $this->lote($detergente, $ubicaciones['Baño'], 1, $marta, dias: null, comprado: 12);
        $this->lote($queso, $ubicaciones['Nevera'], 150, $ana, dias: 12, comprado: 6);
        $this->lote($queso, $ubicaciones['Nevera'], 100, $luis, dias: 26, comprado: 1);
        $this->lote($huevos, $ubicaciones['Nevera'], 11, $luis, dias: 9, comprado: 3);
        $this->lote($arroz, $ubicaciones['Despensa'], 2, $ana, dias: null, comprado: 30);
        $this->lote($garbanzos, $ubicaciones['Despensa'], 6, $marta, dias: null, comprado: 9);

        // --- Movimientos de ejemplo --------------------------------------
        // Ana registra un consumo a nombre de Iker: el caso que justifica
        // separar "registrado por" de "atribuido a".
        $this->consumo($yogur, 1, registrador: $ana, atribuido: $iker, horas: 3);
        $this->consumo($huevos, 1, registrador: $ana, atribuido: $ana, horas: 8);
        $this->consumo($leche, 1, registrador: $luis, atribuido: $luis, horas: 26);

        MovimientoStock::create([
            'producto_id' => $garbanzos->id,
            'entrada_stock_id' => $garbanzos->entradasStock()->first()->id,
            'tipo' => MovimientoStock::TIPO_CORRECCION,
            'cantidad' => 2,
            'usuario_registrador_id' => $marta->id,
            'usuario_atribuido_id' => $marta->id,
            'nota' => 'Corrección de recuento · antes 4',
            'created_at' => now()->subHours(30),
        ]);

        $this->command?->info('Hogar de demo listo. Entra con ana@example.com / password');
    }

    /** Crea un lote y el movimiento de compra que lo originó. */
    private function lote(
        Producto $producto,
        Ubicacion $ubicacion,
        float $cantidad,
        User $comprador,
        ?int $dias,
        int $comprado,
    ): void {
        $entrada = EntradaStock::create([
            'producto_id' => $producto->id,
            'ubicacion_id' => $ubicacion->id,
            'cantidad_restante' => $cantidad,
            'fecha_compra' => now()->subDays($comprado)->toDateString(),
            'fecha_caducidad' => $dias === null ? null : now()->addDays($dias)->toDateString(),
            'abierto' => false,
        ]);

        MovimientoStock::create([
            'producto_id' => $producto->id,
            'entrada_stock_id' => $entrada->id,
            'tipo' => MovimientoStock::TIPO_COMPRA,
            'cantidad' => $cantidad,
            'ubicacion_destino_id' => $ubicacion->id,
            'usuario_registrador_id' => $comprador->id,
            'usuario_atribuido_id' => $comprador->id,
            'created_at' => now()->subDays($comprado),
        ]);
    }

    private function consumo(
        Producto $producto,
        float $cantidad,
        User $registrador,
        User $atribuido,
        int $horas,
    ): void {
        MovimientoStock::create([
            'producto_id' => $producto->id,
            'entrada_stock_id' => $producto->entradasStock()->first()?->id,
            'tipo' => MovimientoStock::TIPO_CONSUMO,
            'cantidad' => $cantidad,
            'usuario_registrador_id' => $registrador->id,
            'usuario_atribuido_id' => $atribuido->id,
            'created_at' => now()->subHours($horas),
        ]);
    }
}
