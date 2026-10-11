<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __invoke(Request $request): View
    {
        $client = $request->user()->client;

        return view('portal.account', [
            'user' => $request->user(),
            'client' => $client,
            'stats' => $client ? [
                'invoiced' => $client->invoices()->sum('total'),
                'paid' => $client->payments()->sum('amount'),
                'outstanding' => $client->invoices()->sum('remaining'),
                'invoices' => $client->invoices()->count(),
            ] : null,
        ]);
    }
}
