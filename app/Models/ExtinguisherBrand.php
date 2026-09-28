<?php

namespace App\Models;

use Database\Factories\ExtinguisherBrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class ExtinguisherBrand extends Model
{
    /** @use HasFactory<ExtinguisherBrandFactory> */
    use HasFactory;

    public function extinguishers(): HasMany
    {
        return $this->hasMany(Extinguisher::class);
    }
}
