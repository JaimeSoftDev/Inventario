<?php

namespace Database\Factories;

use App\Models\UnidadMedida;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnidadMedida>
 */
class UnidadMedidaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->randomElement(['Unidad', 'Kilogramo', 'Litro', 'Paquete']),
            'abreviatura' => fake()->randomElement(['ud', 'kg', 'l', 'paq']),
        ];
    }
}
