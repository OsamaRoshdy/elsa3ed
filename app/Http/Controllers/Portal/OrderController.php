<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $client = $request->user()->client;

        return view('portal.orders.index', [
            'client' => $client,
            'orders' => $client?->orders()
                ->where('status', '!=', Order::STATUS_DRAFT)
                ->withCount('items')
                ->latest('submitted_at')
                ->paginate(15),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorizeOrder($request, $order);

        return view('portal.orders.show', [
            'order' => $order->load(['items', 'invoice']),
        ]);
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($request, $order);

        if (! $order->canBeCancelledByClient()) {
            return back()->with('error', __('This order can no longer be cancelled.'));
        }

        $order->update(['status' => Order::STATUS_CANCELLED]);

        return back()->with('success', __('Order cancelled.'));
    }

    /**
     * A client may only see their own submitted orders.
     */
    private function authorizeOrder(Request $request, Order $order): void
    {
        $client = $request->user()->client;

        abort_unless($client && $order->client_id === $client->id && ! $order->isDraft(), 404);
    }
}
