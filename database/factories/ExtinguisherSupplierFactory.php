<?php

namespace Database\Factories;

use App\Models\ExtinguisherSupplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExtinguisherSupplier>
 */
class ExtinguisherSupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
        ];
    }
}
