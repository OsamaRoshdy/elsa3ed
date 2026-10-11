<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $client = $user->client;

        return view('portal.dashboard', [
            'client' => $client,
            'stats' => $client ? [
                'outstanding' => $client->invoices()->sum('remaining'),
                'invoices' => $client->invoices()->count(),
                'paid' => $client->payments()->sum('amount'),
                'lastPayment' => $client->payments()->latest('payment_date')->latest('id')->first(),
            ] : null,
            'invoices' => $client?->invoices()->latest('invoice_date')->latest('id')->limit(5)->get() ?? collect(),
            'payments' => $client?->payments()->with('invoice')->latest('payment_date')->latest('id')->limit(5)->get() ?? collect(),
        ]);
    }
}
