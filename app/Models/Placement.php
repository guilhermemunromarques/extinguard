<?php

namespace App\Models;

use Database\Factories\PlacementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable(['code', 'floor_id', 'x', 'y', 'location_description', 'updated_by'])]
class Placement extends Model
{
    /** @use HasFactory<PlacementFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Placement $placement): void {
            $placement->uuid ??= (string) Str::uuid();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('dominio')
            ->logOnly(['code', 'floor_id', 'x', 'y', 'location_description', 'updated_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Posicionamento criado',
                'updated' => 'Posicionamento atualizado',
                'deleted' => 'Posicionamento excluido',
                'restored' => 'Posicionamento restaurado',
                default => "Posicionamento {$eventName}",
            });
    }

    protected function casts(): array
    {
        return [
            'uuid' => 'string',
            'x' => 'decimal:2',
            'y' => 'decimal:2',
        ];
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function extinguisher(): HasOne
    {
        return $this->hasOne(Extinguisher::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
