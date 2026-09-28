<?php

use Illuminate\Support\Facades\Route;

//Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', 'permission:dashboard.view'])->group(function () {
    Route::view('/', 'dashboard')->name('home');
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth', 'verified', 'permission:users.view'])->group(function () {
    Route::livewire('users', 'pages::users.index')->name('users.index');
});

Route::middleware(['auth', 'verified', 'permission:extinguishers.view'])->group(function () {
    Route::livewire('extinguishers', 'pages::extinguishers.index')->name('extinguishers.management');
});

Route::middleware(['auth', 'verified', 'permission:buildings.view'])->group(function () {
    Route::livewire('buildings', 'pages::buildings.index')->name('buildings.management');
});

Route::middleware(['auth', 'verified', 'permission:floors.view'])->group(function () {
    Route::livewire('floors', 'pages::floors.index')->name('floors.management');
});

Route::middleware(['auth', 'verified', 'permission:placements.view'])->group(function () {
    Route::livewire('placements', 'pages::placements.index')->name('placements.management');
});

require __DIR__ . '/settings.php';
