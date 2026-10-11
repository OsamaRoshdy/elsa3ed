<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'owing' => ['nullable', 'boolean'],
        ]);

        $clients = Client::query()
            ->search($filters['q'] ?? null)
            ->when($filters['owing'] ?? false, fn ($query) => $query->where('balance', '>', 0))
            ->withCount('invoices')
            ->withExists('users')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.clients.index', [
            'clients' => $clients,
            'filters' => $filters,
        ]);
    }

    public function show(Client $client): View
    {
        return view('admin.clients.show', [
            'client' => $client->load('users'),
            'stats' => [
                'invoices' => $client->invoices()->count(),
                'invoiced' => $client->invoices()->sum('total'),
                'paid' => $client->payments()->sum('amount'),
                'outstanding' => $client->invoices()->sum('remaining'),
            ],
            'invoices' => $client->invoices()->latest('invoice_date')->latest('id')->paginate(10, ['*'], 'invoices_page'),
            'payments' => $client->payments()->with('invoice')->latest('payment_date')->latest('id')->paginate(10, ['*'], 'payments_page'),
        ]);
    }
}
