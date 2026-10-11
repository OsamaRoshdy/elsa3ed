@props(['invoice', 'invoiceRoute' => 'invoices.show'])

{{-- Daftra generates the PDF on request, which can take several seconds. --}}
<div class="mb-6 flex flex-wrap items-center gap-2" x-data="{ preparing: false }">
    <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="btn-primary"><x-icon name="document" class="size-4" /> {{ __('View PDF') }}</a>
    <a href="{{ route('invoices.pdf', [$invoice, 'download' => 1]) }}" data-no-loader class="btn-secondary" @click="preparing = true; setTimeout(() => preparing = false, 12000)">
        <x-icon name="download" class="size-4" ::class="preparing && 'animate-bounce'" />
        <span x-show="!preparing">{{ __('Download PDF') }}</span>
        <span x-cloak x-show="preparing">{{ __('Preparing PDF…') }}</span>
    </a>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <x-stat-card :label="__('Total')" icon="document" tone="sky"><x-money :amount="$invoice->total" :currency="$invoice->currency" /></x-stat-card>
    <x-stat-card :label="__('Paid')" icon="banknotes" tone="emerald"><x-money :amount="$invoice->paid" :currency="$invoice->currency" /></x-stat-card>
    <x-stat-card :label="__('Remaining')" icon="wallet" :tone="$invoice->remaining > 0 ? 'rose' : 'emerald'"><x-money :amount="$invoice->remaining" :currency="$invoice->currency" /></x-stat-card>
</div>

<div class="card mt-6 p-6">
    <dl class="grid grid-cols-2 gap-6 sm:grid-cols-4">
        <div>
            <dt class="text-xs font-medium text-slate-500">{{ __('Invoice #') }}</dt>
            <dd class="mt-1 font-semibold text-slate-900">#{{ $invoice->invoice_number ?? $invoice->daftra_id }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium text-slate-500">{{ __('Date') }}</dt>
            <dd class="mt-1 font-semibold text-slate-900">{{ $invoice->invoice_date?->translatedFormat('d M Y') ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium text-slate-500">{{ __('Status') }}</dt>
            <dd class="mt-1"><x-invoice-status :status="$invoice->status" /></dd>
        </div>
        <div>
            <dt class="text-xs font-medium text-slate-500">{{ __('Last synchronized') }}</dt>
            <dd class="mt-1 text-sm text-slate-700">{{ $invoice->last_synced_at?->diffForHumans() ?? '—' }}</dd>
        </div>
    </dl>
</div>

<div class="card mt-6 overflow-hidden">
    <h2 class="px-5 py-4 font-semibold text-slate-900">{{ __('Payments for this invoice') }}</h2>
    @if ($invoice->payments->isEmpty())
        <x-empty-state icon="banknotes" :title="__('No payments recorded')" />
    @else
        <x-payment-table :payments="$invoice->payments->sortByDesc('payment_date')" :invoice-route="$invoiceRoute" />
    @endif
</div>
