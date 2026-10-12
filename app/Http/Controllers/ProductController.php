<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Fitment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Tag;
use App\Support\Garage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer'],
            'brand' => ['nullable', 'integer'],
            'model' => ['nullable', 'integer'],
            'year' => ['nullable', 'integer', 'min:'.Fitment::FIRST_YEAR, 'max:'.Fitment::lastYear()],
            'tag' => ['nullable', 'integer'],
            'stock' => ['nullable', 'in:in_stock,available,low,out'],
            'sort' => ['nullable', 'in:'.implode(',', array_keys(Product::SORTS))],
        ]);

        // The admin chooses the default order (Admin → Settings) and whether clients may change it.
        $defaultSort = Setting::get('catalogue_sort', Product::DEFAULT_SORT);
        $clientsCanSort = (bool) Setting::get('catalogue_client_sort', true);
        $sort = $clientsCanSort && ! empty($filters['sort']) ? $filters['sort'] : $defaultSort;

        $defaultThreshold = (int) config('services.daftra.low_stock_threshold', 10);

        // A model only counts when it belongs to the chosen brand (e.g. after switching brand in the sidebar).
        $brand = empty($filters['brand']) ? null : Brand::find($filters['brand']);
        $carModel = $brand && ! empty($filters['model']) ? $brand->carModels()->find($filters['model']) : null;

        $showsOutOfStock = Product::storeShowsOutOfStock();

        $products = Product::visibleInStore()
            ->with(['brands', 'categories', 'tags'])
            ->search($filters['q'] ?? null)
            ->forCategory($filters['category'] ?? null)
            ->forBrand($filters['brand'] ?? null)
            ->fitsVehicle($carModel?->id, $filters['year'] ?? null)
            ->forTag($filters['tag'] ?? null)
            ->when(($filters['stock'] ?? null) === 'in_stock', fn ($query) => $query->inStock())
            ->when(($filters['stock'] ?? null) === 'out', fn ($query) => $query->whereNotNull('stock_quantity')->where('stock_quantity', '<=', 0))
            ->when(($filters['stock'] ?? null) === 'low', fn ($query) => $query->lowStock())
            ->when(($filters['stock'] ?? null) === 'available', fn ($query) => $query->where(function ($query) use ($defaultThreshold) {
                $query->whereNull('stock_quantity')
                    ->orWhereRaw('stock_quantity > COALESCE(NULLIF(low_stock_threshold, 0), ?)', [$defaultThreshold]);
            }))
            ->sorted($sort)
            ->paginate(24)
            ->withQueryString();

        $categories = Category::whereHas('products', fn ($query) => $query->visibleInStore())->orderBy('name')->get(['id', 'name']);
        $tags = Tag::whereHas('products', fn ($query) => $query->visibleInStore())->orderBy('name')->get(['id', 'name', 'color']);
        $brands = Brand::withCount(['products' => fn ($query) => $query->visibleInStore()])
            ->whereHas('products', fn ($query) => $query->visibleInStore())
            ->orderBy('name')
            ->get(['id', 'name', 'logo_path']);

        return view('products.index', [
            'products' => $products,
            'categories' => $categories,
            'tags' => $tags,
            'brands' => $brands,
            'activeCategory' => $categories->firstWhere('id', (int) ($filters['category'] ?? 0)),
            'activeTag' => $tags->firstWhere('id', (int) ($filters['tag'] ?? 0)),
            'activeBrand' => $brand,
            'activeModel' => $carModel,
            'filters' => $filters,
            'sort' => $sort,
            'clientsCanSort' => $clientsCanSort,
            'showsOutOfStock' => $showsOutOfStock,
            'lastSyncedAt' => Product::max('last_synced_at'),
        ]);
    }

    public function show(Product $product, Garage $garage): View
    {
        abort_unless($product->isVisibleInStore(), 404);

        $product->load(['brands', 'categories', 'tags', 'fitments.carModel.brand']);

        // Related products share at least one category with this product.
        $related = $product->categories->isEmpty() ? collect() : Product::visibleInStore()
            ->whereHas('categories', fn ($query) => $query->whereKey($product->categories->modelKeys()))
            ->whereKeyNot($product->id)
            ->with('tags')
            ->limit(4)
            ->get();

        return view('products.show', [
            'product' => $product,
            'related' => $related,
            'fitsSelectedCar' => $garage->fits($product),
            'selectedCar' => $garage->label(),
        ]);
    }
}
