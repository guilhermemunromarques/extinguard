<?php

namespace Database\Factories;

use App\Models\Inspection;
use App\Models\InspectionItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InspectionItem>
 */
class InspectionItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'inspection_id' => Inspection::factory(),
            'key' => fake()->unique()->slug(2),
            'label' => fake()->sentence(3),
            'result' => fake()->randomElement(['passed', 'failed', 'not_applicable']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
