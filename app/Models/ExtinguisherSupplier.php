<?php

namespace App\Models;

use Database\Factories\ExtinguisherSupplierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class ExtinguisherSupplier extends Model
{
    /** @use HasFactory<ExtinguisherSupplierFactory> */
    use HasFactory;

    public function extinguishers(): HasMany
    {
        return $this->hasMany(Extinguisher::class);
    }
}
