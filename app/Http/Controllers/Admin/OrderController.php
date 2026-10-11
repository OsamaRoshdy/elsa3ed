<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(Order::STATUSES)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $orders = Order::query()
            ->with('client')
            ->withCount('items')
            ->when(
                $filters['status'] ?? null,
                fn ($query, $status) => $query->where('status', $status),
                fn ($query) => $query->where('status', '!=', Order::STATUS_DRAFT),
            )
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(function ($query) use ($term) {
                    $query->whereHas('client', fn ($query) => $query->where('name', 'like', "%{$term}%"));

                    if (is_numeric(ltrim(str_ireplace('ORD-', '', $term), '0'))) {
                        $query->orWhere('id', (int) ltrim(str_ireplace('ORD-', '', $term), '0'));
                    }
                });
            })
            ->orderByRaw('CASE status WHEN ? THEN 0 WHEN ? THEN 1 ELSE 2 END', [Order::STATUS_SUBMITTED, Order::STATUS_PROCESSING])
            ->latest('submitted_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => $filters,
            'counts' => Order::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(Order $order): View
    {
        abort_if($order->isDraft(), 404);

        return view('admin.orders.show', [
            'order' => $order->load(['items.product', 'client', 'user', 'invoice']),
            'hasInvoices' => Invoice::where('client_id', $order->client_id)->exists(),
            'oldInvoice' => old('invoice_id') ? Invoice::where('client_id', $order->client_id)->find(old('invoice_id')) : null,
        ]);
    }

    /**
     * Select2 search: the order's client invoices that aren't linked to another order.
     */
    public function invoices(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $invoices = Invoice::query()
            ->where('client_id', $order->client_id)
            ->whereNotIn('id', Order::whereNotNull('invoice_id')->whereKeyNot($order->id)->select('invoice_id'))
            ->when($validated['q'] ?? null, fn ($query, $term) => $query->where('invoice_number', 'like', "%{$term}%"))
            ->latest('invoice_date')->latest('id')
            ->simplePaginate(20, ['*'], 'page', $validated['page'] ?? 1);

        return response()->json([
            'results' => $invoices->getCollection()->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'text' => sprintf('#%s — %s — %s %s', $invoice->invoice_number ?? $invoice->daftra_id, $invoice->invoice_date?->format('Y-m-d') ?? '—', number_format((float) $invoice->total, 2), $invoice->currency ?: config('services.daftra.currency')),
            ])->values(),
            'pagination' => ['more' => $invoices->hasMorePages()],
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        abort_if($order->isDraft(), 404);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['processing', 'complete', 'cancel', 'reopen', 'note'])],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
            'invoice_id' => [
                'nullable',
                'required_if:action,complete',
                'integer',
                Rule::exists('invoices', 'id')->where('client_id', $order->client_id),
                Rule::unique('orders', 'invoice_id')->ignore($order->id),
            ],
        ]);

        $data = ['admin_notes' => $validated['admin_notes'] ?? $order->admin_notes];

        $data += match ($validated['action']) {
            'processing' => ['status' => Order::STATUS_PROCESSING],
            'complete' => ['status' => Order::STATUS_COMPLETED, 'invoice_id' => $validated['invoice_id'], 'completed_at' => now()],
            'cancel' => ['status' => Order::STATUS_CANCELLED],
            'reopen' => ['status' => Order::STATUS_SUBMITTED, 'invoice_id' => null, 'completed_at' => null],
            'note' => [],
        };

        $order->update($data);

        return back()->with('success', __('Order updated.'));
    }
}
