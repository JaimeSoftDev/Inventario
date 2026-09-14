<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Ubicacion;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database con datos de ejemplo de un hogar.
     */
    public function run(): void
    {
        // El primer usuario del hogar puede atribuir movimientos a los demás
        // (p. ej. el "administrador" de la despensa).
        $admin = User::factory()->conPermisoDeAtribucion()->create([
            'name' => 'Ana',
            'email' => 'ana@example.com',
        ]);

        $companero = User::factory()->create([
            'name' => 'Luis',
            'email' => 'luis@example.com',
        ]);

        $ubicaciones = collect(['Nevera', 'Despensa', 'Congelador'])
            ->map(fn (string $nombre) => Ubicacion::create(['nombre' => $nombre]));

        $unidades = collect([
            ['nombre' => 'Unidad', 'abreviatura' => 'ud'],
            ['nombre' => 'Kilogramo', 'abreviatura' => 'kg'],
            ['nombre' => 'Litro', 'abreviatura' => 'l'],
        ])->map(fn (array $datos) => UnidadMedida::create($datos));

        $categorias = collect(['Lácteos', 'Limpieza', 'Congelados'])
            ->map(fn (string $nombre) => Categoria::create(['nombre' => $nombre]));

        Producto::factory()->create([
            'nombre' => 'Leche entera',
            'categoria_id' => $categorias->first(fn ($c) => $c->nombre === 'Lácteos')->id,
            'unidad_medida_id' => $unidades->first(fn ($u) => $u->abreviatura === 'l')->id,
            'ubicacion_por_defecto_id' => $ubicaciones->first(fn ($u) => $u->nombre === 'Nevera')->id,
            'stock_minimo' => 2,
            'dias_caducidad_por_defecto' => 7,
        ]);

        Producto::factory()->create([
            'nombre' => 'Detergente',
            'categoria_id' => $categorias->first(fn ($c) => $c->nombre === 'Limpieza')->id,
            'unidad_medida_id' => $unidades->first(fn ($u) => $u->abreviatura === 'ud')->id,
            'ubicacion_por_defecto_id' => $ubicaciones->first(fn ($u) => $u->nombre === 'Despensa')->id,
            'stock_minimo' => 1,
            'dias_caducidad_por_defecto' => null,
        ]);

        $this->command?->info("Usuarios de prueba: {$admin->email} / luis@example.com (password: 'password')");
    }
}
