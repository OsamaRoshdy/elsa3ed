<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The client's draft order acts as their cart. Nothing is sent to Daftra.
 */
class CartController extends Controller
{
    public function show(Request $request): View
    {
        $order = $this->draftOrder($request->user(), create: false);

        return view('portal.orders.cart', [
            'client' => $request->user()->client,
            'order' => $order?->load('items.product'),
        ]);
    }

    /**
     * Mini-cart data for the header dropdown.
     */
    public function summary(Request $request): JsonResponse
    {
        return response()->json($this->summaryData($this->draftOrder($request->user(), create: false)));
    }

    /**
     * Adds a product. Returns JSON for the in-page "Add to order" button, or redirects without JavaScript.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if ($product->stockStatus() === Product::STOCK_OUT) {
            return $request->wantsJson()
                ? response()->json(['message' => __('This product is out of stock.')], 422)
                : back()->with('error', __('This product is out of stock.'));
        }

        $order = $this->draftOrder($request->user());
        $item = $order->items()->firstOrNew(['product_id' => $product->id]);
        $requested = ($item->quantity ?? 0) + $validated['quantity'];
        $quantity = $this->capToStock($product, $requested);

        $item->fill([
            'daftra_product_id' => $product->daftra_id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->price,
            'quantity' => $quantity,
        ])->save();

        $order->recalculateTotal();

        $message = $quantity < $requested
            ? __('Only :quantity of :product available — your order has the maximum.', ['quantity' => $quantity, 'product' => $product->name])
            : __(':product added to your order.', ['product' => $product->name]);

        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'limited' => $quantity < $requested] + $this->summaryData($order));
        }

        return back()->with('success', $message);
    }

    public function update(Request $request, OrderItem $item): RedirectResponse
    {
        $order = $this->authorizedDraftItem($request, $item);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $item->update([
            'quantity' => $item->product ? $this->capToStock($item->product, $validated['quantity']) : $validated['quantity'],
        ]);
        $order->recalculateTotal();

        return back();
    }

    public function destroy(Request $request, OrderItem $item): RedirectResponse
    {
        $order = $this->authorizedDraftItem($request, $item);

        $item->delete();
        $order->recalculateTotal();

        return back()->with('success', __('Item removed.'));
    }

    public function submit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $order = $this->draftOrder($request->user(), create: false);

        if (! $order || $order->items()->doesntExist()) {
            return back()->with('error', __('Your order is empty.'));
        }

        DB::transaction(function () use ($order, $validated) {
            // Refresh prices from the latest synced data and drop products that are no longer available.
            foreach ($order->items()->with('product')->get() as $item) {
                if ($item->product && $item->product->is_active) {
                    $item->update(['price' => $item->product->price]);
                }
            }

            $order->update([
                'status' => Order::STATUS_SUBMITTED,
                'notes' => $validated['notes'] ?? null,
                'submitted_at' => now(),
            ]);
            $order->recalculateTotal();
        });

        return redirect()->route('orders.show', $order)->with('success', __('Your order was sent. We will review it and confirm it soon.'));
    }

    /**
     * @return array{count: int, total: string, items: array<int, array<string, mixed>>}
     */
    private function summaryData(?Order $order): array
    {
        $currency = config('services.daftra.currency');
        $items = $order?->items()->with('product:id,image')->latest('updated_at')->get() ?? collect();

        return [
            'count' => (int) $items->sum('quantity'),
            'total' => number_format((float) ($order->estimated_total ?? 0), 2).' '.$currency,
            'items' => $items->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'name' => $item->product_name,
                'quantity' => $item->quantity,
                'image' => $item->product?->image,
                'line_total' => number_format($item->lineTotal(), 2).' '.$currency,
            ])->values()->all(),
        ];
    }

    private function draftOrder(User $user, bool $create = true): ?Order
    {
        $client = $user->client;

        abort_unless($client, 403, __('Your account is not linked yet'));

        $query = Order::where('client_id', $client->id)
            ->where('user_id', $user->id)
            ->where('status', Order::STATUS_DRAFT);

        if (! $create) {
            return $query->first();
        }

        return $query->firstOrCreate([], [
            'client_id' => $client->id,
            'user_id' => $user->id,
            'status' => Order::STATUS_DRAFT,
        ]);
    }

    private function authorizedDraftItem(Request $request, OrderItem $item): Order
    {
        $order = $item->order;

        abort_unless(
            $order->isDraft() && $order->user_id === $request->user()->id && $order->client_id === $request->user()->client?->id,
            404
        );

        return $order;
    }

    /**
     * Never let a client request more than the stock Daftra reports (untracked products are unlimited).
     */
    private function capToStock(Product $product, int $quantity): int
    {
        if ($product->stock_quantity === null) {
            return $quantity;
        }

        return max(1, min($quantity, (int) floor((float) $product->stock_quantity)));
    }
}
