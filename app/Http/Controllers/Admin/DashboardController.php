<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\SyncRun;
use App\Models\WebhookLog;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'products' => Product::count(),
                'clients' => Client::count(),
                'outstanding' => Invoice::sum('remaining'),
                'lowStock' => Product::active()->lowStock()->count(),
            ],
            'lastRun' => SyncRun::latest('id')->first(),
            'newOrders' => Order::where('status', Order::STATUS_SUBMITTED)->count(),
            'lowStockProducts' => Product::active()->lowStock()->orderBy('stock_quantity')->limit(5)->get(),
            'topBalances' => Client::where('balance', '>', 0)->orderByDesc('balance')->limit(5)->get(),
            'recentWebhooks' => WebhookLog::latest('id')->limit(5)->get(),
        ]);
    }
}
