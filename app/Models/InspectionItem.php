<?php

namespace App\Models;

use Database\Factories\InspectionItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['uuid', 'inspection_id', 'key', 'label', 'result', 'notes'])]
class InspectionItem extends Model
{
    /** @use HasFactory<InspectionItemFactory> */
    use HasFactory;

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }
}
