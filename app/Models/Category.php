<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'image_path'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    public const IMAGE_DISK = 'public';

    protected static function booted(): void
    {
        static::deleted(function (Category $category) {
            $category->deleteImageFile();
        });
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->image_path ? Storage::disk(self::IMAGE_DISK)->url($this->image_path) : null);
    }

    public function deleteImageFile(): void
    {
        if ($this->image_path) {
            Storage::disk(self::IMAGE_DISK)->delete($this->image_path);
        }
    }
}
