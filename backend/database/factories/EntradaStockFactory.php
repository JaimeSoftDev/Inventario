<?php

namespace Database\Factories;

use App\Models\EntradaStock;
use App\Models\Producto;
use App\Models\Ubicacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EntradaStock>
 */
class EntradaStockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'producto_id' => Producto::factory(),
            'ubicacion_id' => Ubicacion::factory(),
            'cantidad_restante' => fake()->randomFloat(3, 1, 10),
            'fecha_compra' => now()->subDays(fake()->numberBetween(0, 10))->toDateString(),
            'fecha_caducidad' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
            'precio_unitario' => fake()->randomFloat(2, 0.5, 20),
            'abierto' => false,
            'nota' => null,
        ];
    }
}
