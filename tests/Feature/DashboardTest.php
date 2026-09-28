<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('guests are redirected to the login page from the application root', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    Permission::firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web']);
    $user->givePermissionTo('dashboard.view');

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the application root renders the dashboard for authorized users', function () {
    $user = User::factory()->create();
    Permission::firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web']);
    $user->givePermissionTo('dashboard.view');

    $this->actingAs($user);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Visão geral do gerenciamento de extintores.');
});

test('dashboard shows the current user and logout action', function () {
    $user = User::factory()->create(['name' => 'Maria da Silva']);
    Permission::firstOrCreate(['name' => 'dashboard.view', 'guard_name' => 'web']);
    $user->givePermissionTo('dashboard.view');

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Maria da Silva')
        ->assertSee('Configurações')
        ->assertSee('Sair')
        ->assertSee('action="' . route('logout') . '"', false);
});
