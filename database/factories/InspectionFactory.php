<?php

namespace Database\Factories;

use App\Models\Extinguisher;
use App\Models\Inspection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Inspection>
 */
class InspectionFactory extends Factory
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
            'extinguisher_id' => Extinguisher::factory(),
            'inspector_id' => null,
            'inspection_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'status' => 'completed',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
