<?php

namespace App\Models;

use Database\Factories\CarModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A vehicle model of a brand, e.g. Kia → Cerato. (Named CarModel to avoid clashing with Eloquent's Model.)
 */
#[Fillable(['brand_id', 'name'])]
class CarModel extends Model
{
    /** @use HasFactory<CarModelFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasMany<Fitment, $this>
     */
    public function fitments(): HasMany
    {
        return $this->hasMany(Fitment::class);
    }
}
