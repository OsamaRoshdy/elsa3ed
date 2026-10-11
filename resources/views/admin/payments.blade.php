<x-layouts.app :title="__('Payments')">
    <x-page-header :title="__('Payments')" :subtitle="__(':count payments synced from Daftra', ['count' => number_format($payments->total())])" />

    <div class="card mb-6 p-4">
        <x-search-form :placeholder="__('Search by client name...')" />
    </div>

    <div class="card overflow-hidden">
        @if ($payments->isEmpty())
            <x-empty-state icon="banknotes" :title="__('No payments found')" />
        @else
            <x-payment-table :payments="$payments" show-client invoice-route="admin.invoices.show" />
            <div class="border-t border-slate-100 px-4 py-3">{{ $payments->links() }}</div>
        @endif
    </div>
</x-layouts.app>
