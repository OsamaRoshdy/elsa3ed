<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in([Invoice::STATUS_PAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_UNPAID, Invoice::STATUS_OVERPAID])],
        ]);

        $client = $request->user()->client;

        $invoices = $client?->invoices()
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('invoice_date')->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('portal.invoices.index', [
            'client' => $client,
            'invoices' => $invoices,
            'status' => $validated['status'] ?? null,
        ]);
    }

    public function show(Request $request, Invoice $invoice): View
    {
        $client = $request->user()->client;

        // A client may only ever see their own invoices.
        abort_unless($client && $invoice->client_id === $client->id, 404);

        return view('portal.invoices.show', [
            'invoice' => $invoice->load('payments.invoice'),
        ]);
    }
}
