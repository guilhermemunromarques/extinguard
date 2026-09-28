<?php

namespace App\Models;

use Database\Factories\FloorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['building_id', 'floor_number', 'name'])]
class Floor extends Model
{
    /** @use HasFactory<FloorFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Floor $floor): void {
            $floor->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'uuid' => 'string',
        ];
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class);
    }
}
