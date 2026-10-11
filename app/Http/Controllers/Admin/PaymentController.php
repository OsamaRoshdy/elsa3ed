<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $payments = Payment::query()
            ->with(['client', 'invoice'])
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->whereHas('client', fn ($query) => $query->where('name', 'like', "%{$term}%")))
            ->latest('payment_date')->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.payments', [
            'payments' => $payments,
            'filters' => $filters,
        ]);
    }
}
