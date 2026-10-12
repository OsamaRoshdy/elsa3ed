<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('storefront.home', [
            'stats' => [
                'products' => Product::visibleInStore()->count(),
                'categories' => Category::whereHas('products', fn ($query) => $query->visibleInStore())->count(),
                'brands' => Brand::count(),
                'models' => CarModel::count(),
            ],
            'categories' => Category::withCount(['products' => fn ($query) => $query->visibleInStore()])
                ->whereHas('products', fn ($query) => $query->visibleInStore())
                ->orderByDesc('products_count')
                ->limit(10)
                ->get(),
            // Newest in-stock parts that have a photo.
            'featured' => Product::visibleInStore()
                ->inStock()
                ->with(['tags', 'brands'])
                ->whereNotNull('image')
                ->latest('id')
                ->limit(8)
                ->get(),
            // Every brand we sell parts for; the ones with the most parts first.
            'brands' => Brand::withCount(['products' => fn ($query) => $query->visibleInStore()])
                ->orderByDesc('products_count')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
