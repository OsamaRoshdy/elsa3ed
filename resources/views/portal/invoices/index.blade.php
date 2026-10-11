<x-layouts.app :title="__('Invoices')">
    <x-page-header :title="__('Invoices')" :subtitle="__('All invoices issued to your account.')" />

    @if (! $client)
        @include('portal.not-linked')
    @else
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach (['' => __('All'), 'unpaid' => __('Unpaid'), 'partial' => __('Partially paid'), 'paid' => __('Paid')] as $value => $label)
                <a href="{{ route('invoices.index', array_filter(['status' => $value])) }}" @class([
                    'rounded-xl px-3.5 py-2 text-sm font-medium transition',
                    'bg-brand-600 text-white shadow-sm' => (string) $status === (string) $value,
                    'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => (string) $status !== (string) $value,
                ])>{{ $label }}</a>
            @endforeach
        </div>

        <div class="card overflow-hidden">
            @if ($invoices->isEmpty())
                <x-empty-state icon="document" :title="__('No invoices found')" />
            @else
                <x-invoice-table :invoices="$invoices" />
                <div class="border-t border-slate-100 px-4 py-3">{{ $invoices->links() }}</div>
            @endif
        </div>
    @endif
</x-layouts.app>
