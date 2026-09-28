<?php

use App\Models\Extinguisher;
use App\Models\ExtinguisherBrand;
use App\Models\ExtinguisherSupplier;
use App\Models\ExtinguisherType;
use App\Models\Floor;
use App\Models\Placement;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;

function grantDomainPermission(User $user, string $permission): void
{
    Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    $user->givePermissionTo($permission);
}

test('user without placement permission cannot create a placement', function () {
    $user = User::factory()->create();
    $floor = Floor::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/placements', [
            'code' => 'PL-API-001',
            'floor_id' => $floor->id,
        ])
        ->assertForbidden();
});

test('authorized user can create update and delete a placement', function () {
    $user = User::factory()->create();
    grantDomainPermission($user, 'placements.create');
    grantDomainPermission($user, 'placements.update');
    grantDomainPermission($user, 'placements.delete');
    $floor = Floor::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/placements', [
        'code' => 'PL-API-001',
        'floor_id' => $floor->id,
        'location_description' => 'Entrada principal',
    ]);

    $response->assertCreated()->assertJsonPath('data.code', 'PL-API-001');
    $placement = Placement::query()->where('code', 'PL-API-001')->firstOrFail();

    $this->actingAs($user)
        ->patchJson("/api/placements/{$placement->id}", ['location_description' => 'Recepcao'])
        ->assertOk()
        ->assertJsonPath('data.location_description', 'Recepcao');

    $this->actingAs($user)
        ->deleteJson("/api/placements/{$placement->id}")
        ->assertNoContent();

    expect(Placement::withTrashed()->find($placement->id)->deleted_at)->not->toBeNull();
});

test('authorized user can create an extinguisher and its creation is audited', function () {
    $user = User::factory()->create();
    grantDomainPermission($user, 'extinguishers.create');
    $type = ExtinguisherType::factory()->create();
    $brand = ExtinguisherBrand::factory()->create();
    $supplier = ExtinguisherSupplier::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/extinguishers', [
        'serial_number' => 'EXT-API-001',
        'extinguisher_type_id' => $type->id,
        'extinguisher_brand_id' => $brand->id,
        'supplier_id' => $supplier->id,
        'capacity' => 5,
        'capacity_unit' => 'L',
        'next_refill_date' => now()->format('Y-m-01'),
        'next_maintenance_year' => now()->year,
        'extinguishing_capacity' => '2A-20B-C',
        'status' => 'active',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.serial_number', 'EXT-API-001')
        ->assertJsonPath('data.supplier', $supplier->name)
        ->assertJsonPath('data.capacity_unit', 'L')
        ->assertJsonPath('data.extinguishing_capacity', '2A-20B-C')
        ->assertJsonPath('data.next_refill_date', now()->startOfMonth()->format('Y-m-d'));

    $extinguisher = Extinguisher::query()->where('serial_number', 'EXT-API-001')->firstOrFail();

    expect(Activity::query()->forSubject($extinguisher)->where('event', 'created')->exists())->toBeTrue();
});

test('extinguisher API rejects malformed extinguishing capacity groups', function () {
    $user = User::factory()->create();
    grantDomainPermission($user, 'extinguishers.create');
    $type = ExtinguisherType::factory()->create();
    $supplier = ExtinguisherSupplier::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/extinguishers', [
            'serial_number' => 'EXT-API-INVALID-001',
            'extinguisher_type_id' => $type->id,
            'supplier_id' => $supplier->id,
            'capacity' => 5,
            'capacity_unit' => 'kg',
            'extinguishing_capacity' => '2A-20',
            'next_refill_date' => now()->format('Y-m-d'),
            'next_maintenance_year' => now()->year,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['extinguishing_capacity']);
});
