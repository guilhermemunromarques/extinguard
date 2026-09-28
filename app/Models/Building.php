<?php

namespace App\Models;

use Database\Factories\BuildingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name'])]
class Building extends Model
{
    /** @use HasFactory<BuildingFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Building $building): void {
            $building->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'uuid' => 'string',
        ];
    }

    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class);
    }
}
