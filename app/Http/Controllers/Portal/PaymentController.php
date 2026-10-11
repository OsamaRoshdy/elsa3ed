<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __invoke(Request $request): View
    {
        $client = $request->user()->client;

        return view('portal.payments', [
            'client' => $client,
            'totalPaid' => $client?->payments()->sum('amount') ?? 0,
            'payments' => $client?->payments()->with('invoice')->latest('payment_date')->latest('id')->paginate(15),
        ]);
    }
}
