<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Ubicacion;
use App\Models\UnidadMedida;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(3, true),
            'descripcion' => fake()->optional()->sentence(),
            'categoria_id' => Categoria::factory(),
            'unidad_medida_id' => UnidadMedida::factory(),
            'ubicacion_por_defecto_id' => Ubicacion::factory(),
            'stock_minimo' => fake()->randomFloat(3, 0, 5),
            'dias_caducidad_por_defecto' => fake()->optional()->numberBetween(3, 365),
            'precio_referencia' => fake()->randomFloat(2, 0.5, 20),
            'notas' => fake()->optional()->sentence(),
        ];
    }
}
