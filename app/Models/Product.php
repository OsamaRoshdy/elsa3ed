<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['daftra_id', 'sku', 'name', 'description', 'image', 'category', 'price', 'stock_quantity', 'low_stock_threshold', 'is_active', 'last_synced_at'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    public const STOCK_AVAILABLE = 'available';

    public const STOCK_LOW = 'low';

    public const STOCK_OUT = 'out';

    /**
     * Catalogue sort options (key => English label; translated in the views).
     */
    public const SORTS = [
        'name_asc' => 'Name: A to Z',
        'name_desc' => 'Name: Z to A',
        'price_asc' => 'Price: low to high',
        'price_desc' => 'Price: high to low',
        'available_first' => 'Availability: in stock first',
        'stock_desc' => 'Availability: most stock first',
    ];

    public const DEFAULT_SORT = 'name_asc';

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            $product->sort_name = static::sortKey((string) $product->name);
        });
    }

    /**
     * Builds the alphabetical sort key for a product name:
     * Arabic names first (group 0), then Latin (1), then names starting with digits/symbols (2);
     * leading punctuation is ignored and Arabic spelling variants are unified (أ إ آ ٱ → ا, ى → ي, ة → ه,
     * no tatweel or diacritics), so "(يمين) غماز" sorts under ي and "أويل" next to "اويل".
     */
    public static function sortKey(string $name): string
    {
        $key = preg_replace('/\p{Mn}|\x{0640}/u', '', $name);
        $key = strtr($key, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ى' => 'ي', 'ة' => 'ه']);
        $key = trim(preg_replace('/\s+/u', ' ', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $key)));

        $group = match (true) {
            (bool) preg_match('/^\p{Arabic}/u', $key) => '0',
            (bool) preg_match('/^\p{Latin}/u', $key) => '1',
            default => '2',
        };

        return mb_substr($group.$key, 0, 255);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock_quantity' => 'decimal:2',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * Products whose stock is not tracked in Daftra (stock_quantity = null) are treated as available.
     */
    public function stockStatus(): string
    {
        if ($this->stock_quantity === null) {
            return self::STOCK_AVAILABLE;
        }

        $quantity = (float) $this->stock_quantity;

        if ($quantity <= 0) {
            return self::STOCK_OUT;
        }

        if ($quantity <= $this->lowStockThreshold()) {
            return self::STOCK_LOW;
        }

        return self::STOCK_AVAILABLE;
    }

    /**
     * Brands are assigned locally by the admin; a product can belong to several brands.
     *
     * @return BelongsToMany<Brand, $this>
     */
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class)->orderBy('name');
    }

    /**
     * Categories are assigned locally by the admin; a product can belong to several categories.
     * (The "category" column is Daftra's own category and is no longer shown to clients.)
     *
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->orderBy('name');
    }

    /**
     * Labels such as "Original" or "Korea aftermarket".
     *
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->orderBy('name');
    }

    /**
     * The vehicles (model + years) this product fits.
     *
     * @return HasMany<Fitment, $this>
     */
    public function fitments(): HasMany
    {
        return $this->hasMany(Fitment::class);
    }

    public function lowStockThreshold(): int
    {
        return $this->low_stock_threshold ?: (int) config('services.daftra.low_stock_threshold', 10);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Products whose stock isn't tracked in Daftra (stock_quantity = null) count as in stock.
     */
    public function scopeInStock(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query->whereNull('stock_quantity')->orWhere('stock_quantity', '>', 0));
    }

    /**
     * Products shown on the storefront: active ones, minus out-of-stock ones when the admin hides them (Admin → Settings).
     */
    public function scopeVisibleInStore(Builder $query): void
    {
        $query->active()->when(! static::storeShowsOutOfStock(), fn (Builder $query) => $query->inStock());
    }

    public static function storeShowsOutOfStock(): bool
    {
        return (bool) Setting::get('catalogue_show_out_of_stock', true);
    }

    public function isVisibleInStore(): bool
    {
        return $this->is_active && (static::storeShowsOutOfStock() || $this->stock_quantity === null || (float) $this->stock_quantity > 0);
    }

    public function scopeForBrand(Builder $query, ?int $brandId): void
    {
        if (! $brandId) {
            return;
        }

        $query->whereHas('brands', fn (Builder $query) => $query->whereKey($brandId));
    }

    public function scopeForCategory(Builder $query, ?int $categoryId): void
    {
        if (! $categoryId) {
            return;
        }

        $query->whereHas('categories', fn (Builder $query) => $query->whereKey($categoryId));
    }

    public function scopeForTag(Builder $query, ?int $tagId): void
    {
        if (! $tagId) {
            return;
        }

        $query->whereHas('tags', fn (Builder $query) => $query->whereKey($tagId));
    }

    /**
     * Products that fit the given model, optionally in a specific year.
     */
    public function scopeFitsVehicle(Builder $query, ?int $carModelId, ?int $year = null): void
    {
        if (! $carModelId) {
            return;
        }

        $query->whereHas('fitments', function (Builder $query) use ($carModelId, $year) {
            $query->where('car_model_id', $carModelId)
                ->when($year, fn (Builder $query, int $year) => $query->coveringYear($year));
        });
    }

    public function scopeLowStock(Builder $query): void
    {
        $defaultThreshold = (int) config('services.daftra.low_stock_threshold', 10);

        $query->whereNotNull('stock_quantity')
            ->where('stock_quantity', '>', 0)
            ->whereRaw('stock_quantity <= COALESCE(NULLIF(low_stock_threshold, 0), ?)', [$defaultThreshold]);
    }

    /**
     * Products whose stock isn't tracked in Daftra count as available (and as "most stock").
     */
    public function scopeSorted(Builder $query, string $sort): void
    {
        $defaultThreshold = (int) config('services.daftra.low_stock_threshold', 10);

        match ($sort) {
            // Z→A reverses the letters but keeps Arabic first and symbols/numbers last.
            'name_desc' => $query->orderByRaw('SUBSTR(sort_name, 1, 1) ASC')->orderBy('sort_name', 'desc'),
            'price_asc' => $query->orderBy('price')->orderBy('sort_name'),
            'price_desc' => $query->orderBy('price', 'desc')->orderBy('sort_name'),
            'available_first' => $query
                ->orderByRaw('CASE WHEN stock_quantity IS NULL OR stock_quantity > COALESCE(NULLIF(low_stock_threshold, 0), ?) THEN 0 WHEN stock_quantity > 0 THEN 1 ELSE 2 END', [$defaultThreshold])
                ->orderBy('sort_name'),
            'stock_desc' => $query
                ->orderByRaw('CASE WHEN stock_quantity IS NULL THEN 0 ELSE 1 END')
                ->orderBy('stock_quantity', 'desc')
                ->orderBy('sort_name'),
            default => $query->orderBy('sort_name'),
        };
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term) {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%");
        });
    }
}
