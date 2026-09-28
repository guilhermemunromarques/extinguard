<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function grantUserPermission(User $user, string $permission): void
{
    Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
    $user->givePermissionTo($permission);
}

function prepareUserRoles(): void
{
    Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => 'web']);
}

test('users page requires view permission', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('users.index'))->assertForbidden();
});

test('authorized user can see the users page', function () {
    $user = User::factory()->create();
    grantUserPermission($user, 'users.view');
    $this->actingAs($user);

    $this->get(route('users.index'))
        ->assertOk()
        ->assertSee('Usuários');
});

test('user roles are displayed in Brazilian Portuguese', function () {
    prepareUserRoles();
    foreach (['Technician', 'Maintenance'] as $roleName) {
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
    }

    $admin = User::factory()->create();
    grantUserPermission($admin, 'users.view');
    $admin->assignRole('Administrator');

    foreach (['Technician', 'Maintenance', 'Viewer'] as $roleName) {
        $user = User::factory()->create();
        $user->assignRole($roleName);
    }

    $this->actingAs($admin);

    $this->get(route('users.index'))
        ->assertOk()
        ->assertSee('Administrador')
        ->assertSee('Técnico')
        ->assertSee('Manutenção')
        ->assertSee('Visualizador');
});

test('authorized administrator can create a viewer user', function () {
    prepareUserRoles();
    $admin = User::factory()->create();
    grantUserPermission($admin, 'users.view');
    grantUserPermission($admin, 'users.create');
    grantUserPermission($admin, 'users.manage');
    $this->actingAs($admin);

    Livewire::test('pages::users.index')
        ->call('createUser')
        ->set('name', 'Pessoa Nova')
        ->set('email', 'pessoa@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('role', 'Viewer')
        ->call('saveUser')
        ->assertHasNoErrors();

    $created = User::where('email', 'pessoa@example.com')->firstOrFail();

    expect($created->name)->toBe('Pessoa Nova')
        ->and(Hash::check('password', $created->password))->toBeTrue()
        ->and($created->hasRole('Viewer'))->toBeTrue();
});

test('authorized administrator can edit and deactivate another user', function () {
    prepareUserRoles();
    $admin = User::factory()->create();
    grantUserPermission($admin, 'users.view');
    grantUserPermission($admin, 'users.update');
    grantUserPermission($admin, 'users.delete');
    grantUserPermission($admin, 'users.manage');
    $target = User::factory()->create(['name' => 'Nome Original']);
    $target->assignRole('Viewer');
    $this->actingAs($admin);

    Livewire::test('pages::users.index')
        ->call('editUser', $target->id)
        ->set('name', 'Nome Atualizado')
        ->call('saveUser')
        ->call('deactivateUser', $target->id)
        ->assertHasNoErrors();

    expect($target->refresh()->name)->toBe('Nome Atualizado')
        ->and($target->is_active)->toBeFalse();

    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'usuarios',
        'subject_type' => User::class,
        'subject_id' => $target->id,
        'description' => 'Role do usuário atualizada',
    ]);
});

test('administrator cannot deactivate their own account', function () {
    $admin = User::factory()->create();
    grantUserPermission($admin, 'users.view');
    grantUserPermission($admin, 'users.delete');
    $this->actingAs($admin);

    Livewire::test('pages::users.index')
        ->call('deactivateUser', $admin->id)
        ->assertForbidden();

    expect($admin->refresh()->is_active)->toBeTrue();
});
