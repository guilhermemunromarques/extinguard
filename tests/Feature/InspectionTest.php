<?php

use App\ExtinguisherStatus;
use App\Models\Extinguisher;
use App\Models\Inspection;
use App\Models\InspectionItem;
use App\Models\User;
use Illuminate\Support\Carbon;

test('an extinguisher exposes inspections and the latest inspection', function () {
    $extinguisher = Extinguisher::factory()->create();
    $older = Inspection::factory()->for($extinguisher)->create([
        'inspection_date' => '2026-01-15',
    ]);
    $latest = Inspection::factory()->for($extinguisher)->create([
        'inspection_date' => '2026-08-20',
    ]);

    expect($extinguisher->inspections)->toHaveCount(2)
        ->and($extinguisher->latestInspection->is($latest))->toBeTrue()
        ->and($extinguisher->last_inspection_date)->toEqual(Carbon::parse('2026-08-20'))
        ->and($older->exists)->toBeTrue();
});

test('inspection can be created by an inspector and has items', function () {
    $inspector = User::factory()->create();
    $inspection = Inspection::factory()->create(['inspector_id' => $inspector->id]);
    $item = InspectionItem::factory()->for($inspection)->create([
        'key' => 'correct_location',
        'label' => 'Localização correta',
        'result' => 'passed',
    ]);

    expect($inspection->inspector->is($inspector))->toBeTrue()
        ->and($inspection->items->first()->is($item))->toBeTrue()
        ->and($item->inspection->is($inspection))->toBeTrue();
});

test('inspection history remains append-only when a newer inspection is created', function () {
    $extinguisher = Extinguisher::factory()->create();
    $first = Inspection::factory()->for($extinguisher)->create([
        'inspection_date' => '2026-02-01',
        'notes' => 'Primeira inspeção',
    ]);

    Inspection::factory()->for($extinguisher)->create([
        'inspection_date' => '2026-03-01',
        'notes' => 'Nova inspeção',
    ]);

    expect($first->refresh()->notes)->toBe('Primeira inspeção')
        ->and($extinguisher->fresh()->inspections)->toHaveCount(2);
});

test('extinguisher status uses the supported backed enum', function () {
    $extinguisher = Extinguisher::factory()->create([
        'status' => ExtinguisherStatus::Maintenance,
    ]);

    expect($extinguisher->status)->toBe(ExtinguisherStatus::Maintenance);
});

test('inspection does not introduce scheduling fields', function () {
    $inspection = Inspection::factory()->create();

    expect($inspection->getAttributes())->not->toHaveKey('next_inspection_date');
});
