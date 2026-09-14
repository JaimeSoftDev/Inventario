<?php

namespace Database\Factories;

use App\Models\Ubicacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ubicacion>
 */
class UbicacionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->randomElement(['Nevera', 'Despensa', 'Congelador', 'Garaje', 'Baño']).' '.fake()->unique()->numerify('##'),
        ];
    }
}
