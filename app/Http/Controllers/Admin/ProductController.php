<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Category;
use App\Models\Fitment;
use App\Models\Product;
use App\Models\Tag;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer'],
            'brand' => ['nullable', 'integer'],
            'tag' => ['nullable', 'integer'],
            'low' => ['nullable', 'boolean'],
        ]);

        $products = Product::query()
            ->with(['brands', 'categories', 'tags'])
            ->search($filters['q'] ?? null)
            ->forCategory($filters['category'] ?? null)
            ->forBrand($filters['brand'] ?? null)
            ->forTag($filters['tag'] ?? null)
            ->when($filters['low'] ?? false, fn ($query) => $query->lowStock())
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.products', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
            'tags' => Tag::orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    /**
     * Products come from Daftra; only the locally managed categories, brands, tags and fitments are editable here.
     */
    public function edit(Product $product): View
    {
        return view('admin.product-edit', [
            'product' => $product->load(['brands', 'categories', 'tags', 'fitments']),
            'brands' => Brand::with('carModels:id,brand_id,name')->orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'tags' => Tag::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $years = ['nullable', 'integer', 'min:'.Fitment::FIRST_YEAR, 'max:'.Fitment::lastYear()];

        $data = $request->validate([
            'brands' => ['nullable', 'array'],
            'brands.*' => ['integer', Rule::exists('brands', 'id')],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', Rule::exists('categories', 'id')],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', Rule::exists('tags', 'id')],
            'fitments' => ['nullable', 'array'],
            'fitments.*.car_model_id' => ['required', 'integer', Rule::exists('car_models', 'id')],
            'fitments.*.year_from' => $years,
            'fitments.*.year_to' => [...$years, function (string $attribute, mixed $value, Closure $fail) use ($request) {
                $yearFrom = $request->input(str_replace('.year_to', '.year_from', $attribute));

                if (filled($value) && filled($yearFrom) && (int) $value < (int) $yearFrom) {
                    $fail(__('The "to" year must be the same as or after the "from" year.'));
                }
            }],
        ]);

        $fitments = collect($data['fitments'] ?? [])
            ->map(fn (array $fitment) => [
                'car_model_id' => (int) $fitment['car_model_id'],
                'year_from' => $fitment['year_from'] ?? null,
                'year_to' => $fitment['year_to'] ?? null,
            ])
            ->unique(fn (array $fitment) => implode('-', $fitment));

        // A product that fits a Kia model is also a Kia product.
        $fitmentBrandIds = CarModel::whereKey($fitments->pluck('car_model_id'))->pluck('brand_id');

        DB::transaction(function () use ($product, $data, $fitments, $fitmentBrandIds) {
            $product->brands()->sync(collect($data['brands'] ?? [])->map(fn ($id) => (int) $id)->merge($fitmentBrandIds)->unique()->all());
            $product->categories()->sync($data['categories'] ?? []);
            $product->tags()->sync($data['tags'] ?? []);
            $product->fitments()->delete();
            $product->fitments()->createMany($fitments->all());
        });

        return redirect()->route('admin.products.index')->with('success', __('Product updated.'));
    }
}
