<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SyncRun;
use App\Services\DaftraException;
use App\Services\DaftraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SyncController extends Controller
{
    public function index(DaftraService $daftra): View
    {
        return view('admin.sync', [
            'isConfigured' => $daftra->isConfigured(),
            'baseUrl' => config('services.daftra.base_url'),
            'runs' => SyncRun::latest('id')->paginate(15),
        ]);
    }

    public function store(DaftraService $daftra): RedirectResponse
    {
        try {
            $run = $daftra->syncAll('manual');
        } catch (DaftraException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if ($run->status !== SyncRun::STATUS_SUCCESS) {
            return back()->with('error', __('Synchronization failed. Please try again.').' '.$run->error);
        }

        return back()->with('success', __('Synchronization completed: :products products, :clients clients, :invoices invoices, :payments payments.', [
            'products' => $run->products_count,
            'clients' => $run->clients_count,
            'invoices' => $run->invoices_count,
            'payments' => $run->payments_count,
        ]));
    }

    public function test(DaftraService $daftra): RedirectResponse
    {
        try {
            $daftra->testConnection();
        } catch (DaftraException $exception) {
            return back()->with('error', __('Connection failed:').' '.$exception->getMessage());
        }

        return back()->with('success', __('Connected to Daftra successfully.'));
    }
}
