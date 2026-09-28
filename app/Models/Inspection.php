<?php

namespace App\Models;

use Database\Factories\InspectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['uuid', 'extinguisher_id', 'inspector_id', 'inspection_date', 'status', 'notes'])]
class Inspection extends Model
{
    /** @use HasFactory<InspectionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'inspection_date' => 'date',
        ];
    }

    public function extinguisher(): BelongsTo
    {
        return $this->belongsTo(Extinguisher::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InspectionItem::class);
    }
}
