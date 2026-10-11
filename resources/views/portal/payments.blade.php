<x-layouts.app :title="__('Payments')">
    <x-page-header :title="__('Payments')" :subtitle="__('All payments received from you.')" />

    @if (! $client)
        @include('portal.not-linked')
    @else
        <x-stat-card :label="__('Total Paid')" icon="banknotes" tone="emerald" class="mb-6 sm:max-w-xs"><x-money :amount="$totalPaid" /></x-stat-card>

        <div class="card overflow-hidden">
            @if ($payments->isEmpty())
                <x-empty-state icon="banknotes" :title="__('No payments yet')" />
            @else
                <x-payment-table :payments="$payments" />
                <div class="border-t border-slate-100 px-4 py-3">{{ $payments->links() }}</div>
            @endif
        </div>
    @endif
</x-layouts.app>
