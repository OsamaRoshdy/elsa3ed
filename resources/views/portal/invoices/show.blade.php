<x-layouts.app :title="__('Invoice #:number', ['number' => $invoice->invoice_number])">
    <a href="{{ route('invoices.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-900">
        <x-icon name="chevron" class="size-4 rotate-180 rtl:rotate-0" /> {{ __('Back to invoices') }}
    </a>
    <x-page-header :title="__('Invoice #:number', ['number' => $invoice->invoice_number ?? $invoice->daftra_id])" />
    <x-invoice-detail :invoice="$invoice" />
</x-layouts.app>
