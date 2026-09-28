<?php

namespace Database\Factories;

use App\Models\Extinguisher;
use App\Models\ExtinguisherBrand;
use App\Models\ExtinguisherSupplier;
use App\Models\ExtinguisherType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Extinguisher>
 */
class ExtinguisherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $serialNumber = 'EXT-'.str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT);

        return [
            'uuid' => Str::uuid(),
            'placement_id' => null,
            'serial_number' => $serialNumber,
            'extinguisher_type_id' => ExtinguisherType::factory(),
            'extinguisher_brand_id' => ExtinguisherBrand::factory(),
            'supplier_id' => ExtinguisherSupplier::factory(),
            'capacity' => fake()->randomElement([1, 2, 5, 10, 20]),
            'capacity_unit' => fake()->randomElement(['L', 'kg']),
            'extinguishing_capacity' => fake()->randomElement(['2A-20B-C', '5B-C', '2A']),
            'maintenance_seal' => 'SEAL-'.str_pad(fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'status' => fake()->randomElement(['active', 'maintenance', 'decommissioned']),
            'next_refill_date' => fake()->dateTimeBetween('now', '+2 years')->format('Y-m-01'),
            'next_maintenance_year' => fake()->numberBetween((int) date('Y'), (int) date('Y') + 2),
            'notes' => fake()->optional()->text(100),
            'updated_by' => null,
        ];
    }
}
