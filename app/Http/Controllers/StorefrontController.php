<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\CarModel;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user()?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('storefront.home', [
            'stats' => [
                'products' => Product::active()->count(),
                'categories' => Category::whereHas('products', fn ($query) => $query->active())->count(),
                'brands' => Brand::count(),
                'models' => CarModel::count(),
            ],
            'categories' => Category::withCount(['products' => fn ($query) => $query->active()])
                ->whereHas('products', fn ($query) => $query->active())
                ->orderByDesc('products_count')
                ->limit(10)
                ->get(),
            // Newest in-stock parts that have a photo.
            'featured' => Product::active()
                ->with(['tags', 'brands'])
                ->whereNotNull('image')
                ->where(fn ($query) => $query->whereNull('stock_quantity')->orWhere('stock_quantity', '>', 0))
                ->latest('id')
                ->limit(8)
                ->get(),
            // Every brand we sell parts for; the ones with the most parts first.
            'brands' => Brand::withCount(['products' => fn ($query) => $query->active()])
                ->orderByDesc('products_count')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
