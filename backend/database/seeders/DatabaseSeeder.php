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
 * Hogar de ejemplo para desarrollo, con datos que ejercitan todos los
 * estados de la interfaz: caducidad inminente, stock bajo, un producto
 * repartido en varios lotes, una corrección de recuento y consumos
 * atribuidos a terceros.
 *
 *   php artisan migrate:fresh --seed
 *
 * Nunca en producción: el `migrate:fresh` con el que se invoca tira todas
 * las tablas, y estos usuarios tienen contraseñas de juguete. El alta real
 * de un servidor está en la fase 3 de DEPLOY.md.
 *
 * El catálogo de ubicaciones y unidades es deliberadamente el mismo que
 * crea ese bloque de DEPLOY.md: si cambia uno, cambia el otro, o el hogar
 * de desarrollo deja de parecerse al de verdad.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // --- El hogar ----------------------------------------------------
        // Solo Jaime puede atribuir movimientos a los demás. Que los otros
        // dos no puedan no es un descuido: es lo que permite comprobar en
        // desarrollo el 403 del servicio y que el gesto de "a nombre de
        // otro" no se ofrezca a quien no tiene el permiso.
        $jaime = $this->miembro('Jaime', 'jaimesoftdev@gmail.com', atribuye: true);
        $antonio = $this->miembro('Antonio', 'antonio@example.com');
        $samuel = $this->miembro('Samuel', 'samuel@example.com');

        // --- Catálogo (mismo que DEPLOY.md) ------------------------------
        $ubicaciones = collect(['Nevera', 'Despensa', 'Congelador', 'Baño'])
            ->mapWithKeys(fn (string $nombre) => [$nombre => Ubicacion::create(['nombre' => $nombre])]);

        $unidades = collect([
            ['nombre' => 'Unidad', 'abreviatura' => 'uds'],
            ['nombre' => 'Kilogramo', 'abreviatura' => 'kg'],
            ['nombre' => 'Litro', 'abreviatura' => 'L'],
            ['nombre' => 'Paquete', 'abreviatura' => 'packs'],
            ['nombre' => 'Gramo', 'abreviatura' => 'g'],
            ['nombre' => 'Brik', 'abreviatura' => 'briks'],
            ['nombre' => 'Lata', 'abreviatura' => 'latas'],
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

        // En latas, que es como se guardan de verdad y además deja esa
        // unidad a la vista y no solo en el catálogo.
        $garbanzos = Producto::create([
            'nombre' => 'Garbanzos',
            'categoria_id' => $categorias['Despensa']->id,
            'unidad_medida_id' => $unidades['latas']->id,
            'ubicacion_por_defecto_id' => $ubicaciones['Despensa']->id,
            'stock_minimo' => 1,
        ]);

        // --- Lotes con su compra correspondiente -------------------------
        $this->lote($yogur, $ubicaciones['Nevera'], 8, $jaime, dias: 2, comprado: 4);
        // Leche repartida en dos lotes: uno urgente y otro holgado. Es el
        // caso que justifica FEFO y el desglose por lotes de la ficha.
        $this->lote($leche, $ubicaciones['Nevera'], 1, $antonio, dias: 2, comprado: 5);
        $this->lote($leche, $ubicaciones['Nevera'], 1, $jaime, dias: 20, comprado: 2);
        $this->lote($detergente, $ubicaciones['Baño'], 1, $samuel, dias: null, comprado: 12);
        $this->lote($queso, $ubicaciones['Nevera'], 150, $jaime, dias: 12, comprado: 6);
        $this->lote($queso, $ubicaciones['Nevera'], 100, $antonio, dias: 26, comprado: 1);
        $this->lote($huevos, $ubicaciones['Nevera'], 11, $antonio, dias: 9, comprado: 3);
        $this->lote($arroz, $ubicaciones['Despensa'], 2, $jaime, dias: null, comprado: 30);
        $this->lote($garbanzos, $ubicaciones['Despensa'], 9, $samuel, dias: null, comprado: 9);

        // --- Movimientos de ejemplo --------------------------------------
        // Jaime registra un consumo a nombre de Samuel: el caso que
        // justifica separar "registrado por" de "atribuido a".
        $this->consumo($yogur, 1, registrador: $jaime, atribuido: $samuel, horas: 3);
        $this->consumo($huevos, 1, registrador: $jaime, atribuido: $jaime, horas: 8);
        $this->consumo($leche, 1, registrador: $antonio, atribuido: $antonio, horas: 26);

        // Recuento a ojo: había tres latas menos de las que decía la app.
        $this->correccion($garbanzos, 6, $samuel, horas: 30);

        $this->command?->info(
            'Hogar de desarrollo listo. Entra con jaimesoftdev@gmail.com, '
            .'antonio@example.com o samuel@example.com; la contraseña de cada '
            .'uno es su propio nombre (Jaime, Antonio, Samuel).'
        );
    }

    /** La contraseña de cada miembro es su nombre: esto es solo desarrollo. */
    private function miembro(string $nombre, string $email, bool $atribuye = false): User
    {
        return User::create([
            'name' => $nombre,
            'email' => $email,
            'password' => Hash::make($nombre),
            'puede_atribuir_a_otros' => $atribuye,
        ]);
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

    /**
     * Ajusta el lote además de anotar el movimiento. Escribir solo el
     * apunte dejaría a la app diciendo "=6, antes 9" junto a un stock de 9.
     */
    private function correccion(Producto $producto, float $cantidad, User $usuario, int $horas): void
    {
        $entrada = $producto->entradasStock()->first();
        $anterior = (float) $entrada->cantidad_restante;

        $entrada->update(['cantidad_restante' => $cantidad]);

        MovimientoStock::create([
            'producto_id' => $producto->id,
            'entrada_stock_id' => $entrada->id,
            'tipo' => MovimientoStock::TIPO_CORRECCION,
            'cantidad' => $cantidad,
            'usuario_registrador_id' => $usuario->id,
            'usuario_atribuido_id' => $usuario->id,
            'nota' => 'Corrección de recuento · antes '.rtrim(rtrim(number_format($anterior, 3, '.', ''), '0'), '.'),
            'created_at' => now()->subHours($horas),
        ]);
    }
}
