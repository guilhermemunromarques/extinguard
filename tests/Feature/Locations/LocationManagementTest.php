<?php

use App\Models\Building;
use App\Models\Extinguisher;
use App\Models\Floor;
use App\Models\Placement;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function grantLocationPermission(User $user, string ...$permissions): void
{
    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        $user->givePermissionTo($permission);
    }
}

function prepareLocationManager(User $user): void
{
    Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
    $user->assignRole('Administrator');
    grantLocationPermission(
        $user,
        'buildings.view',
        'buildings.create',
        'buildings.update',
        'buildings.delete',
        'floors.view',
        'floors.create',
        'floors.update',
        'floors.delete',
        'placements.view',
        'placements.create',
        'placements.update',
        'placements.delete',
        'placements.assign_extinguisher',
    );
}

test('location pages require their view permissions', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::buildings.index')->assertForbidden();
    Livewire::test('pages::floors.index')->assertForbidden();
    Livewire::test('pages::placements.index')->assertForbidden();
});

test('authorized user can create and edit a building', function () {
    $user = User::factory()->create();
    prepareLocationManager($user);
    $this->actingAs($user);

    Livewire::test('pages::buildings.index')
        ->call('createBuilding')
        ->set('name', 'Edifício Central')
        ->call('saveBuilding')
        ->assertHasNoErrors();

    $building = Building::query()->where('name', 'Edifício Central')->firstOrFail();

    Livewire::test('pages::buildings.index')
        ->call('editBuilding', $building->id)
        ->set('name', 'Edifício Administrativo')
        ->call('saveBuilding')
        ->assertHasNoErrors();

    expect($building->refresh()->name)->toBe('Edifício Administrativo');
});

test('building deletion is blocked when it has floors', function () {
    $user = User::factory()->create();
    prepareLocationManager($user);
    $building = Building::factory()->create();
    Floor::factory()->create(['building_id' => $building->id]);
    $this->actingAs($user);

    Livewire::test('pages::buildings.index')
        ->call('deleteBuilding', $building->id)
        ->assertSee('Não é possível excluir um edifício que possui andares.');

    expect(Building::find($building->id))->not->toBeNull();
});

test('authorized user can create and filter floors by building', function () {
    $user = User::factory()->create();
    prepareLocationManager($user);
    $building = Building::factory()->create(['name' => 'Bloco A']);
    $this->actingAs($user);

    Livewire::test('pages::floors.index')
        ->call('createFloor')
        ->set('building_id', $building->id)
        ->set('floor_number', 1)
        ->set('name', 'Primeiro andar')
        ->call('saveFloor')
        ->assertHasNoErrors()
        ->set('buildingFilter', (string) $building->id)
        ->assertSee('Primeiro andar');
});

test('floor number must be unique inside the same building', function () {
    $user = User::factory()->create();
    prepareLocationManager($user);
    $building = Building::factory()->create();
    Floor::factory()->create(['building_id' => $building->id, 'floor_number' => 1]);
    $this->actingAs($user);

    Livewire::test('pages::floors.index')
        ->call('createFloor')
        ->set('building_id', $building->id)
        ->set('floor_number', 1)
        ->set('name', 'Duplicado')
        ->call('saveFloor')
        ->assertHasErrors(['floor_number']);
});

test('floor deletion is blocked when it has placements', function () {
    $user = User::factory()->create();
    prepareLocationManager($user);
    $floor = Floor::factory()->create();
    Placement::factory()->create(['floor_id' => $floor->id]);
    $this->actingAs($user);

    Livewire::test('pages::floors.index')
        ->call('deleteFloor', $floor->id)
        ->assertSee('Não é possível excluir um andar que possui posicionamentos.');
});

test('authorized user can create a placement and assign a free extinguisher', function () {
    $user = User::factory()->create();
    prepareLocationManager($user);
    $floor = Floor::factory()->create();
    $extinguisher = Extinguisher::factory()->create(['placement_id' => null]);
    $this->actingAs($user);

    Livewire::test('pages::placements.index')
        ->call('createPlacement')
        ->set('code', 'PL-MANAGED-001')
        ->set('floor_id', $floor->id)
        ->set('x', '12.50')
        ->set('y', '20.25')
        ->set('location_description', 'Entrada principal')
        ->set('extinguisher_id', $extinguisher->id)
        ->call('savePlacement')
        ->assertHasNoErrors();

    $placement = Placement::query()->where('code', 'PL-MANAGED-001')->firstOrFail();

    expect($placement->floor_id)->toBe($floor->id)
        ->and($extinguisher->refresh()->placement_id)->toBe($placement->id);
});

test('placement assignment rejects an extinguisher already assigned elsewhere', function () {
    $user = User::factory()->create();
    prepareLocationManager($user);
    $floor = Floor::factory()->create();
    $otherPlacement = Placement::factory()->create();
    $extinguisher = Extinguisher::factory()->create(['placement_id' => $otherPlacement->id]);
    $this->actingAs($user);

    Livewire::test('pages::placements.index')
        ->call('createPlacement')
        ->set('code', 'PL-MANAGED-002')
        ->set('floor_id', $floor->id)
        ->set('extinguisher_id', $extinguisher->id)
        ->call('savePlacement')
        ->assertHasErrors(['extinguisher_id']);
});

test('placement deletion is blocked when it has an extinguisher', function () {
    $user = User::factory()->create();
    prepareLocationManager($user);
    $placement = Placement::factory()->create();
    Extinguisher::factory()->create(['placement_id' => $placement->id]);
    $this->actingAs($user);

    Livewire::test('pages::placements.index')
        ->call('deletePlacement', $placement->id)
        ->assertSee('Não é possível excluir um posicionamento com extintor atribuído.');
});
