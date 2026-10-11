<x-layouts.app :title="__('Invoices')">
    <x-page-header :title="__('Invoices')" :subtitle="__(':count invoices synced from Daftra', ['count' => number_format($invoices->total())])" />

    <div class="card mb-6 p-4">
        <x-search-form :placeholder="__('Search by invoice number or client...')">
            <select name="status" class="input sm:w-48" onchange="this.form.submit()">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (['unpaid' => __('Unpaid'), 'partial' => __('Partially paid'), 'paid' => __('Paid'), 'overpaid' => __('Overpaid')] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-search-form>
    </div>

    <div class="card overflow-hidden">
        @if ($invoices->isEmpty())
            <x-empty-state icon="document" :title="__('No invoices found')" />
        @else
            <x-invoice-table :invoices="$invoices" route="admin.invoices.show" show-client />
            <div class="border-t border-slate-100 px-4 py-3">{{ $invoices->links() }}</div>
        @endif
    </div>
</x-layouts.app>
