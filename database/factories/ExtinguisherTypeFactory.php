<?php

namespace Database\Factories;

use App\Models\ExtinguisherType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExtinguisherType>
 */
class ExtinguisherTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['ABC', 'BC', 'CO2', 'Water', 'Dry Chemical', 'Foam'];
        $type = fake()->randomElement($types);

        return [
            'name' => $type . ' ' . fake()->unique()->uuid(),
        ];
    }
}
