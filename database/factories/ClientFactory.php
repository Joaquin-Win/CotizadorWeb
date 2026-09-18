<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empresa' => fake()->company(),
            'cuit' => fake()->numerify('30-########-#'),
            'nombre_contacto' => fake()->firstName(),
            'apellido_contacto' => fake()->lastName(),
            'email' => fake()->unique()->companyEmail(),
            'telefono' => fake()->phoneNumber(),
            'direccion' => fake()->address(),
            'notas' => null,
            'is_active' => true,
        ];
    }
}
