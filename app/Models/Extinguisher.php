<?php

namespace App\Models;

use App\ExtinguisherStatus;
use Database\Factories\ExtinguisherFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable(['placement_id', 'serial_number', 'extinguisher_type_id', 'extinguisher_brand_id', 'supplier_id', 'capacity', 'capacity_unit', 'extinguishing_capacity', 'maintenance_seal', 'status', 'next_refill_date', 'next_maintenance_year', 'notes', 'updated_by'])]
class Extinguisher extends Model
{
    /** @use HasFactory<ExtinguisherFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Extinguisher $extinguisher): void {
            $extinguisher->uuid ??= (string) Str::uuid();
        });
    }

    public function setExtinguishingCapacityAttribute(?string $value): void
    {
        $this->attributes['extinguishing_capacity'] = $value === null ? null : strtoupper($value);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('dominio')
            ->logOnly([
                'placement_id',
                'serial_number',
                'extinguisher_type_id',
                'extinguisher_brand_id',
                'supplier_id',
                'capacity',
                'capacity_unit',
                'extinguishing_capacity',
                'maintenance_seal',
                'status',
                'next_refill_date',
                'next_maintenance_year',
                'notes',
                'updated_by',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => 'Extintor criado',
                'updated' => 'Extintor atualizado',
                'deleted' => 'Extintor excluido',
                'restored' => 'Extintor restaurado',
                default => "Extintor {$eventName}",
            });
    }

    protected function casts(): array
    {
        return [
            'uuid' => 'string',
            'capacity' => 'decimal:2',
            'capacity_unit' => 'string',
            'extinguishing_capacity' => 'string',
            'next_refill_date' => 'date:Y-m-d',
            'next_maintenance_year' => 'integer',
            'status' => ExtinguisherStatus::class,
        ];
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    public function latestInspection(): HasOne
    {
        return $this->hasOne(Inspection::class)->latestOfMany('inspection_date');
    }

    public function getLastInspectionDateAttribute(): mixed
    {
        return $this->relationLoaded('latestInspection')
            ? $this->latestInspection?->inspection_date
            : $this->latestInspection()->value('inspection_date');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ExtinguisherType::class, 'extinguisher_type_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ExtinguisherBrand::class, 'extinguisher_brand_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ExtinguisherSupplier::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
