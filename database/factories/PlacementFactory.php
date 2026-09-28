<?php

namespace Database\Factories;

use App\Models\Floor;
use App\Models\Placement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Placement>
 */
class PlacementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = 'PL-'.str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT);

        return [
            'uuid' => Str::uuid(),
            'code' => $code,
            'floor_id' => Floor::factory(),
            'x' => fake()->randomFloat(2, 0, 1000),
            'y' => fake()->randomFloat(2, 0, 1000),
            'location_description' => fake()->words(3, true),
            'updated_by' => null,
        ];
    }
}
