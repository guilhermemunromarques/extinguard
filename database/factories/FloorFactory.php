<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Floor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Floor>
 */
class FloorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $floorNumber = 0;

        $floorNumber++;

        return [
            'uuid' => Str::uuid(),
            'building_id' => Building::factory(),
            'floor_number' => $floorNumber,
            'name' => 'Andar '.$floorNumber,
        ];
    }
}
