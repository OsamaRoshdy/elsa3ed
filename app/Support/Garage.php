<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Product;

/**
 * The car the visitor selected ("Select your car"), kept in the session.
 */
class Garage
{
    private const SESSION_KEY = 'vehicle';

    /**
     * @var array{brand: Brand, model: CarModel|null, year: int|null}|false|null
     */
    private array|false|null $resolved = null;

    public function select(int $brandId, ?int $modelId, ?int $year): void
    {
        session()->put(self::SESSION_KEY, ['brand' => $brandId, 'model' => $modelId, 'year' => $modelId ? $year : null]);
        $this->resolved = null;
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
        $this->resolved = false;
    }

    /**
     * @return array{brand: Brand, model: CarModel|null, year: int|null}|null
     */
    public function current(): ?array
    {
        if ($this->resolved === null) {
            $this->resolved = $this->resolve() ?? false;
        }

        return $this->resolved ?: null;
    }

    public function label(): ?string
    {
        $vehicle = $this->current();

        if (! $vehicle) {
            return null;
        }

        return trim(implode(' ', array_filter([$vehicle['brand']->name, $vehicle['model']?->name, $vehicle['year']])));
    }

    /**
     * Catalogue filters for the selected car.
     *
     * @return array{brand?: int, model?: int, year?: int}
     */
    public function query(): array
    {
        $vehicle = $this->current();

        return $vehicle ? array_filter([
            'brand' => $vehicle['brand']->id,
            'model' => $vehicle['model']?->id,
            'year' => $vehicle['year'],
        ]) : [];
    }

    /**
     * Whether the product fits the selected car: true / false, or null when there is no car
     * (or no model) selected or the product has no fitment data.
     */
    public function fits(Product $product): ?bool
    {
        $vehicle = $this->current();

        if (! $vehicle || ! $vehicle['model'] || $product->fitments->isEmpty()) {
            return null;
        }

        return $product->fitments->contains(fn ($fitment) => $fitment->car_model_id === $vehicle['model']->id && $fitment->covers($vehicle['year']));
    }

    /**
     * @return array{brand: Brand, model: CarModel|null, year: int|null}|null
     */
    private function resolve(): ?array
    {
        $stored = session(self::SESSION_KEY);

        if (! is_array($stored) || ! ($brand = Brand::find($stored['brand'] ?? null))) {
            return null;
        }

        $model = isset($stored['model']) ? CarModel::where('brand_id', $brand->id)->find($stored['model']) : null;

        return ['brand' => $brand, 'model' => $model, 'year' => $model ? ($stored['year'] ?? null) : null];
    }
}
