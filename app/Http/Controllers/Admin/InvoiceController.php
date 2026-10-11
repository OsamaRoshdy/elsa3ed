<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([Invoice::STATUS_PAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_UNPAID, Invoice::STATUS_OVERPAID])],
        ]);

        $invoices = Invoice::query()
            ->with('client')
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(function ($query) use ($term) {
                    $query->where('invoice_number', 'like', "%{$term}%")
                        ->orWhereHas('client', fn ($query) => $query->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('invoice_date')->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.invoices.index', [
            'invoices' => $invoices,
            'filters' => $filters,
        ]);
    }

    public function show(Invoice $invoice): View
    {
        return view('admin.invoices.show', [
            'invoice' => $invoice->load(['client', 'payments.invoice']),
        ]);
    }
}
