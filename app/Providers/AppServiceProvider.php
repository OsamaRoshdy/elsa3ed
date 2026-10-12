<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Garage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(Garage::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('components.pagination.links');

        // Badge counts for the sidebar: items in the client's draft order, or new orders waiting for the admin.
        View::composer('components.layouts.app', function ($view) {
            $user = auth()->user();

            // Admins get the sidebar portal, except on storefront pages where they preview the store.
            $usesAdminLayout = $user?->isAdmin() && ! request()->routeIs('home', 'products.*');

            $view->with('usesAdminLayout', $usesAdminLayout);

            $view->with('navCounts', match (true) {
                $user?->isAdmin() => ['admin.orders' => Order::where('status', Order::STATUS_SUBMITTED)->count()],
                (bool) $user?->daftra_client_id => ['cart' => (int) OrderItem::whereHas('order', fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->where('status', Order::STATUS_DRAFT))->sum('quantity')],
                default => [],
            });

            // Store header/footer: category menu, brands with models for "Select your car".
            if (! $usesAdminLayout) {
                $view->with([
                    'storeCategories' => Category::whereHas('products', fn ($query) => $query->visibleInStore())->orderBy('name')->get(['id', 'name', 'image_path']),
                    'storeBrands' => Brand::forVehicleFinder(),
                    'garage' => app(Garage::class),
                ]);
            }
        });
    }
}
