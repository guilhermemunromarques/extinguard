<?php

use App\ExtinguisherStatus;
use App\Models\Extinguisher;
use App\Models\ExtinguisherBrand;
use App\Models\ExtinguisherSupplier;
use App\Models\ExtinguisherType;
use App\Models\Placement;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function grantExtinguisherPermission(User $user, string $permission): void
{
    Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    $user->givePermissionTo($permission);
}

function assignExtinguisherRole(User $user, string $roleName): void
{
    Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    $user->assignRole($roleName);
}

function grantExtinguisherManagement(User $user, string $role = 'Technician'): void
{
    assignExtinguisherRole($user, $role);
    grantExtinguisherPermission($user, 'extinguishers.view');
    grantExtinguisherPermission($user, 'extinguishers.create');
    grantExtinguisherPermission($user, 'extinguishers.update');
    grantExtinguisherPermission($user, 'placements.assign_extinguisher');
}

test('user without extinguisher view permission cannot access the management page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::extinguishers.index')->assertForbidden();
});

test('authorized user can create an extinguisher with a free placement', function () {
    $user = User::factory()->create();
    grantExtinguisherManagement($user);
    $type = ExtinguisherType::factory()->create(['name' => 'PQS-ABC']);
    $brand = ExtinguisherBrand::factory()->create(['name' => 'Mocelin']);
    $supplier = ExtinguisherSupplier::factory()->create(['name' => 'Fornecedor Teste']);
    $placement = Placement::factory()->create(['code' => 'RES-001']);
    $this->actingAs($user);

    Livewire::test('pages::extinguishers.index')
        ->call('createExtinguisher')
        ->set('serial_number', 'EXT-NEW-001')
        ->set('extinguisher_type_id', $type->id)
        ->set('extinguisher_brand_id', $brand->id)
        ->set('supplier_id', $supplier->id)
        ->set('capacity', '6')
        ->set('capacity_unit', 'L')
        ->set('extinguishing_capacity', '2A-20B-C')
        ->set('placement_id', $placement->id)
        ->set('next_refill_date', now()->format('Y-m'))
        ->set('next_maintenance_year', (string) now()->year)
        ->set('status', ExtinguisherStatus::Active->value)
        ->call('saveExtinguisher')
        ->assertHasNoErrors();

    $created = Extinguisher::query()->where('serial_number', 'EXT-NEW-001')->firstOrFail();

    expect($created->placement_id)->toBe($placement->id)
        ->and($created->type->is($type))->toBeTrue()
        ->and($created->brand->is($brand))->toBeTrue();
});

test('occupied placement is rejected when creating an extinguisher', function () {
    $user = User::factory()->create();
    grantExtinguisherManagement($user);
    $type = ExtinguisherType::factory()->create();
    $brand = ExtinguisherBrand::factory()->create();
    $supplier = ExtinguisherSupplier::factory()->create();
    $placement = Placement::factory()->create();
    Extinguisher::factory()->create(['placement_id' => $placement->id]);
    $this->actingAs($user);

    Livewire::test('pages::extinguishers.index')
        ->call('createExtinguisher')
        ->set('serial_number', 'EXT-OCCUPIED-001')
        ->set('extinguisher_type_id', $type->id)
        ->set('extinguisher_brand_id', $brand->id)
        ->set('supplier_id', $supplier->id)
        ->set('capacity', '6')
        ->set('capacity_unit', 'L')
        ->set('next_refill_date', now()->format('Y-m'))
        ->set('next_maintenance_year', (string) now()->year)
        ->set('placement_id', $placement->id)
        ->call('saveExtinguisher')
        ->assertHasErrors(['placement_id']);
});

test('maintenance user can update operational fields but cannot change equipment identity', function () {
    $user = User::factory()->create();
    grantExtinguisherManagement($user, 'Maintenance');
    $extinguisher = Extinguisher::factory()->create([
        'serial_number' => 'EXT-ORIGINAL-001',
        'next_maintenance_year' => now()->year,
    ]);
    $this->actingAs($user);

    Livewire::test('pages::extinguishers.index')
        ->call('editExtinguisher', $extinguisher->id)
        ->set('serial_number', 'EXT-CHANGED-001')
        ->set('status', ExtinguisherStatus::Maintenance->value)
        ->set('maintenance_seal', 'SEAL-UPDATED-001')
        ->set('next_refill_date', now()->format('Y-m'))
        ->call('saveExtinguisher')
        ->assertHasNoErrors();

    expect($extinguisher->refresh()->serial_number)->toBe('EXT-ORIGINAL-001')
        ->and($extinguisher->status)->toBe(ExtinguisherStatus::Maintenance)
        ->and($extinguisher->maintenance_seal)->toBe('SEAL-UPDATED-001');
});

test('authorized user can mark an extinguisher as decommissioned', function () {
    $user = User::factory()->create();
    grantExtinguisherManagement($user);
    $extinguisher = Extinguisher::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::extinguishers.index')
        ->call('editExtinguisher', $extinguisher->id)
        ->set('status', ExtinguisherStatus::Decommissioned->value)
        ->set('next_refill_date', now()->format('Y-m'))
        ->call('saveExtinguisher')
        ->assertHasNoErrors();

    expect($extinguisher->refresh()->status)->toBe(ExtinguisherStatus::Decommissioned);
});

test('capacity extintora accepts variable groups and optional group combinations', function () {
    $user = User::factory()->create();
    grantExtinguisherManagement($user);
    $supplier = ExtinguisherSupplier::factory()->create();
    $type = ExtinguisherType::factory()->create();
    $this->actingAs($user);

    foreach (['2A-20B-C', '5B-C', '2A', '12D-80E-F'] as $index => $capacity) {
        Livewire::test('pages::extinguishers.index')
            ->call('createExtinguisher')
            ->set('serial_number', "EXT-FORMAT-{$index}")
            ->set('extinguisher_type_id', $type->id)
            ->set('supplier_id', $supplier->id)
            ->set('capacity', '5')
            ->set('capacity_unit', 'kg')
            ->set('extinguishing_capacity', $capacity)
            ->set('next_refill_date', now()->format('Y-m'))
            ->set('next_maintenance_year', (string) now()->year)
            ->call('saveExtinguisher')
            ->assertHasNoErrors();
    }

    expect(Extinguisher::query()->whereIn('serial_number', ['EXT-FORMAT-0', 'EXT-FORMAT-1', 'EXT-FORMAT-2', 'EXT-FORMAT-3'])->count())->toBe(4);
});

test('brand may be omitted while supplier and dates remain required', function () {
    $user = User::factory()->create();
    grantExtinguisherManagement($user);
    $supplier = ExtinguisherSupplier::factory()->create();
    $type = ExtinguisherType::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::extinguishers.index')
        ->call('createExtinguisher')
        ->set('serial_number', 'EXT-NO-BRAND-001')
        ->set('extinguisher_type_id', $type->id)
        ->set('supplier_id', $supplier->id)
        ->set('capacity', '5')
        ->set('capacity_unit', 'L')
        ->set('next_refill_date', now()->format('Y-m'))
        ->set('next_maintenance_year', (string) now()->year)
        ->call('saveExtinguisher')
        ->assertHasNoErrors();

    expect(Extinguisher::query()->where('serial_number', 'EXT-NO-BRAND-001')->value('extinguisher_brand_id'))->toBeNull();
});

test('capacity extintora rejects malformed group patterns', function () {
    $user = User::factory()->create();
    grantExtinguisherManagement($user);
    $supplier = ExtinguisherSupplier::factory()->create();
    $type = ExtinguisherType::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::extinguishers.index')
        ->call('createExtinguisher')
        ->set('serial_number', 'EXT-INVALID-FORMAT-001')
        ->set('extinguisher_type_id', $type->id)
        ->set('supplier_id', $supplier->id)
        ->set('capacity', '5')
        ->set('capacity_unit', 'L')
        ->set('extinguishing_capacity', '2A-20')
        ->set('next_refill_date', now()->format('Y-m'))
        ->set('next_maintenance_year', (string) now()->year)
        ->call('saveExtinguisher')
        ->assertHasErrors(['extinguishing_capacity']);
});
