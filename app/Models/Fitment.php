<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * "This product fits <model> from <year_from> to <year_to>". A null year means the range is open on that side.
 */
#[Fillable(['product_id', 'car_model_id', 'year_from', 'year_to'])]
class Fitment extends Model
{
    public const FIRST_YEAR = 1980;

    public static function lastYear(): int
    {
        return (int) now()->year + 1;
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<CarModel, $this>
     */
    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }

    /**
     * Fitments that cover the given year (open-ended ranges included).
     */
    public function scopeCoveringYear(Builder $query, int $year): void
    {
        $query->where(fn (Builder $query) => $query->whereNull('year_from')->orWhere('year_from', '<=', $year))
            ->where(fn (Builder $query) => $query->whereNull('year_to')->orWhere('year_to', '>=', $year));
    }

    /**
     * Whether the fitment covers the given year (any year counts when none is given).
     */
    public function covers(?int $year): bool
    {
        if (! $year) {
            return true;
        }

        return (! $this->year_from || $this->year_from <= $year) && (! $this->year_to || $this->year_to >= $year);
    }

    /**
     * Human readable years: "2015 – 2017", "2015", "2015+", "– 2017" or "All years".
     */
    public function yearsLabel(): string
    {
        return match (true) {
            $this->year_from && $this->year_to && $this->year_from === $this->year_to => (string) $this->year_from,
            $this->year_from && $this->year_to => $this->year_from.' – '.$this->year_to,
            (bool) $this->year_from => $this->year_from.'+',
            (bool) $this->year_to => '– '.$this->year_to,
            default => __('All years'),
        };
    }
}
