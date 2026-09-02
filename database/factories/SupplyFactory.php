<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supply>
 */
class SupplyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "name" => $this->faker->word(),
            "type" => $type = $this->faker->randomElement(['piece', 'clay']),
            "date" => $this->faker->dateTimeThisYear(),
            "height" => $type === 'piece' ? $this->faker->randomFloat(1, 5, 30) : null,
            "surface" => $type === 'piece' ? $this->faker->randomFloat(3, 0.1, 0.5) : null,
            "firing" => $type === 'piece' ? $this->faker->randomElement(['low-temp', 'high-temp']) : null,
            'reference' => $type === 'clay' ? $this->faker->regexify('[A-Z]{5}[0-4]{3}') : null,
            'price' => $this->faker->randomFloat(2, 12, 150),

            //éventuellement calculer le prix des pièces en fonction de la formule de calcul utilisée pour les fiches cuisson
        ];
    }
}
