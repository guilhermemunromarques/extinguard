<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('users without dashboard permission cannot access the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertForbidden();
});

test('users with dashboard permission can access the dashboard', function () {
    $user = User::factory()->create();
    Permission::firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web']);
    $user->givePermissionTo('dashboard.view');

    $this->actingAs($user);

    $this->get(route('dashboard'))->assertOk();
});
