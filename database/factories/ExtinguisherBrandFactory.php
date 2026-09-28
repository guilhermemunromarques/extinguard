<?php

namespace Database\Factories;

use App\Models\ExtinguisherBrand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExtinguisherBrand>
 */
class ExtinguisherBrandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $brands = ['Kidde', 'First Alert', 'Pyro-Chem', 'Ansul', 'Fike'];
        $brand = fake()->randomElement($brands);

        return [
            'name' => $brand . ' ' . fake()->unique()->uuid(),
        ];
    }
}
