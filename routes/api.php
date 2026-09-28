<?php

use App\Http\Controllers\ExtinguisherController;
use App\Http\Controllers\PlacementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::apiResource('placements', PlacementController::class)
        ->middlewareFor('index', 'permission:placements.view')
        ->middlewareFor('show', 'permission:placements.view')
        ->middlewareFor('store', 'permission:placements.create')
        ->middlewareFor('update', 'permission:placements.update')
        ->middlewareFor('destroy', 'permission:placements.delete');

    Route::apiResource('extinguishers', ExtinguisherController::class)
        ->middlewareFor('index', 'permission:extinguishers.view')
        ->middlewareFor('show', 'permission:extinguishers.view')
        ->middlewareFor('store', 'permission:extinguishers.create')
        ->middlewareFor('update', 'permission:extinguishers.update')
        ->middlewareFor('destroy', 'permission:extinguishers.delete');
});
