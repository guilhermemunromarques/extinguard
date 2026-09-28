<?php

use App\ExtinguisherStatus;
use App\Models\Building;
use App\Models\Extinguisher;
use App\Models\ExtinguisherBrand;
use App\Models\ExtinguisherType;
use App\Models\Floor;
use App\Models\Placement;
use Spatie\Activitylog\Models\Activity;

test('building has many floors', function () {
    $building = Building::factory()
        ->has(Floor::factory()->count(3))
        ->create();

    expect($building->floors)->toHaveCount(3);
    expect($building->floors->first())->toBeInstanceOf(Floor::class);
});

test('floor belongs to building', function () {
    $floor = Floor::factory()->create();

    expect($floor->building)->toBeInstanceOf(Building::class);
});

test('floor has many placements', function () {
    $floor = Floor::factory()
        ->has(Placement::factory()->count(5))
        ->create();

    expect($floor->placements)->toHaveCount(5);
    expect($floor->placements->first())->toBeInstanceOf(Placement::class);
});

test('placement belongs to floor', function () {
    $placement = Placement::factory()->create();

    expect($placement->floor)->toBeInstanceOf(Floor::class);
});

test('placement has optional extinguisher', function () {
    $placement = Placement::factory()->create();

    expect($placement->extinguisher)->toBeNull();

    $extinguisher = Extinguisher::factory()->create(['placement_id' => $placement->id]);

    expect($placement->refresh()->extinguisher)->toBeInstanceOf(Extinguisher::class);
    expect($placement->extinguisher->id)->toBe($extinguisher->id);
});

test('extinguisher belongs to placement', function () {
    $extinguisher = Extinguisher::factory()->create();

    // Initially no placement (reserve)
    expect($extinguisher->placement)->toBeNull();

    $placement = Placement::factory()->create();
    $extinguisher->update(['placement_id' => $placement->id]);

    expect($extinguisher->refresh()->placement)->toBeInstanceOf(Placement::class);
});

test('one extinguisher per placement uniqueness', function () {
    $placement = Placement::factory()->create();
    $extinguisher1 = Extinguisher::factory()->create(['placement_id' => $placement->id]);

    // Attempting to assign another extinguisher to the same placement should fail
    $extinguisher2 = Extinguisher::factory()->create();

    expect(function () use ($extinguisher2, $placement) {
        $extinguisher2->update(['placement_id' => $placement->id]);
    })->toThrow(Exception::class);
});

test('placement has stable uuid on creation', function () {
    $placement = Placement::factory()->create();

    expect($placement->uuid)->not->toBeNull();
    $uuid = $placement->uuid;

    $placement->refresh();
    expect($placement->uuid)->toBe($uuid);
});

test('extinguisher has stable uuid on creation', function () {
    $extinguisher = Extinguisher::factory()->create();

    expect($extinguisher->uuid)->not->toBeNull();
    $uuid = $extinguisher->uuid;

    $extinguisher->refresh();
    expect($extinguisher->uuid)->toBe($uuid);
});

test('extinguisher has unique serial number', function () {
    $serial = 'EXT-00001';
    Extinguisher::factory()->create(['serial_number' => $serial]);

    expect(function () use ($serial) {
        Extinguisher::factory()->create(['serial_number' => $serial]);
    })->toThrow(Exception::class);
});

test('extinguisher belongs to type and brand', function () {
    $type = ExtinguisherType::factory()->create();
    $brand = ExtinguisherBrand::factory()->create();

    $extinguisher = Extinguisher::factory()
        ->for($type, 'type')
        ->for($brand, 'brand')
        ->create();

    expect($extinguisher->type)->toBeInstanceOf(ExtinguisherType::class);
    expect($extinguisher->brand)->toBeInstanceOf(ExtinguisherBrand::class);
    expect($extinguisher->type->id)->toBe($type->id);
    expect($extinguisher->brand->id)->toBe($brand->id);
});

test('placement soft deletes', function () {
    $placement = Placement::factory()->create();
    $id = $placement->id;

    $placement->delete();

    expect(Placement::find($id))->toBeNull();
    expect(Placement::withTrashed()->find($id))->not->toBeNull();
});

test('extinguisher soft deletes', function () {
    $extinguisher = Extinguisher::factory()->create();
    $id = $extinguisher->id;

    $extinguisher->delete();

    expect(Extinguisher::find($id))->toBeNull();
    expect(Extinguisher::withTrashed()->find($id))->not->toBeNull();
});

test('extinguisher contains required attributes', function () {
    $extinguisher = Extinguisher::factory()->create([
        'serial_number' => 'TEST-001',
        'capacity' => 5.00,
        'extinguishing_capacity' => 7.5,
        'status' => 'active',
        'maintenance_seal' => 'intact',
    ]);

    expect($extinguisher->serial_number)->toBe('TEST-001');
    expect($extinguisher->capacity)->toEqual(5.00);
    expect($extinguisher->extinguishing_capacity)->toEqual(7.5);
    expect($extinguisher->status)->toBe(ExtinguisherStatus::Active);
    expect($extinguisher->maintenance_seal)->toBe('intact');
});

test('placement contains required attributes', function () {
    $placement = Placement::factory()->create([
        'code' => 'PL-001',
        'x' => 100.50,
        'y' => 200.75,
        'location_description' => 'Test Location',
    ]);

    expect($placement->code)->toBe('PL-001');
    expect($placement->x)->toEqual(100.50);
    expect($placement->y)->toEqual(200.75);
    expect($placement->location_description)->toBe('Test Location');
});

test('placement activity log records create update and delete events', function () {
    $placement = Placement::factory()->create(['location_description' => 'Entrada']);

    $placement->update(['location_description' => 'Saida']);
    $placement->delete();

    expect(Activity::query()->where('subject_type', Placement::class)->get())
        ->toHaveCount(3)
        ->each->toMatchArray([
            'log_name' => 'dominio',
        ]);

    expect(Activity::query()->where('subject_type', Placement::class)->pluck('description')->all())
        ->toEqual(['Posicionamento criado', 'Posicionamento atualizado', 'Posicionamento excluido']);
});

test('extinguisher activity log records assignment changes', function () {
    $placement = Placement::factory()->create();
    $extinguisher = Extinguisher::factory()->create();

    $extinguisher->update(['placement_id' => $placement->id]);

    expect(Activity::query()->where('subject_type', Extinguisher::class)->get())
        ->toHaveCount(2)
        ->each->toMatchArray([
            'log_name' => 'dominio',
        ]);

    $updateActivity = Activity::query()
        ->where('subject_type', Extinguisher::class)
        ->where('event', 'updated')
        ->firstOrFail();

    expect($updateActivity->getExtraProperty('attributes.placement_id'))->toBe($placement->id);
});
