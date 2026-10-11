<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\DaftraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Serves an invoice PDF fetched from Daftra on the fly (nothing is stored locally).
 * Falls back to Daftra's documented invoice link if the PDF can't be fetched.
 */
class InvoicePdfController extends Controller
{
    public function __invoke(Request $request, Invoice $invoice, DaftraService $daftra): Response|RedirectResponse
    {
        $user = $request->user();

        // Clients may only download their own invoices.
        abort_unless($user->isAdmin() || ($user->client && $invoice->client_id === $user->client->id), 404);

        $pdf = $daftra->downloadInvoicePdf($invoice);

        if ($pdf !== null) {
            $filename = 'invoice-'.($invoice->invoice_number ?? $invoice->daftra_id).'.pdf';

            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline').'; filename="'.$filename.'"',
                'Cache-Control' => 'private, no-store',
            ]);
        }

        $pdfUrl = $daftra->invoicePdfUrl($invoice);

        if ($pdfUrl) {
            return redirect()->away($pdfUrl);
        }

        return back()->with('error', __('The invoice PDF is not available right now. Please try again later.'));
    }
}
