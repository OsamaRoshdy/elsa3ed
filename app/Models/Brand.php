<?php

namespace App\Models;

use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'logo_path'])]
class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    public const LOGO_DISK = 'public';

    protected static function booted(): void
    {
        static::deleted(function (Brand $brand) {
            $brand->deleteLogoFile();
        });
    }

    /**
     * Brands with their models for the "Select your car" finder (queried once per request).
     *
     * @return Collection<int, Brand>
     */
    public static function forVehicleFinder(): Collection
    {
        return once(fn () => static::with('carModels:id,brand_id,name')->orderBy('name')->get(['id', 'name', 'logo_path']));
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /**
     * @return HasMany<CarModel, $this>
     */
    public function carModels(): HasMany
    {
        return $this->hasMany(CarModel::class)->orderBy('name');
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->logo_path ? Storage::disk(self::LOGO_DISK)->url($this->logo_path) : null);
    }

    public function deleteLogoFile(): void
    {
        if ($this->logo_path) {
            Storage::disk(self::LOGO_DISK)->delete($this->logo_path);
        }
    }
}
