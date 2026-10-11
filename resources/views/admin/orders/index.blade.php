<x-layouts.app :title="__('Orders')">
    <x-page-header :title="__('Orders')" :subtitle="__('Order requests from clients. Create each one manually in Daftra, then link the synced invoice.')" />

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (['' => __('All'), 'submitted' => __('Submitted'), 'processing' => __('Processing'), 'completed' => __('Completed'), 'cancelled' => __('Cancelled')] as $value => $label)
            @php($active = (string) ($filters['status'] ?? '') === (string) $value)
            <a href="{{ route('admin.orders.index', array_filter(['status' => $value, 'q' => $filters['q'] ?? null])) }}" @class([
                'inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-medium transition',
                'bg-brand-600 text-white shadow-sm' => $active,
                'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => ! $active,
            ])>
                {{ $label }}
                @if ($value !== '' && ($counts[$value] ?? 0) > 0)
                    <span @class(['rounded-full px-1.5 text-xs tabular-nums', 'bg-white/20' => $active, 'bg-slate-100' => ! $active])>{{ $counts[$value] }}</span>
                @endif
            </a>
        @endforeach
    </div>

    <div class="card mb-6 p-4">
        <x-search-form :placeholder="__('Search by order number or client...')">
            @if ($filters['status'] ?? null)
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
            @endif
        </x-search-form>
    </div>

    <div class="card overflow-hidden">
        @if ($orders->isEmpty())
            <x-empty-state icon="clipboard" :title="__('No orders found')" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Order #') }}</th>
                            <th>{{ __('Client') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Items') }}</th>
                            <th>{{ __('Estimated total') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td class="font-semibold text-slate-900" dir="ltr">{{ $order->number() }}</td>
                                <td>{{ $order->client?->name ?? '—' }}</td>
                                <td>{{ $order->submitted_at?->translatedFormat('d M Y, h:i A') ?? '—' }}</td>
                                <td class="tabular-nums">{{ $order->items_count }}</td>
                                <td><x-money :amount="$order->estimated_total" /></td>
                                <td><x-order-status :status="$order->status" /></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:text-brand-700">{{ __('Open') }} <x-icon name="chevron" class="size-4 rtl:-scale-x-100" /></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">{{ $orders->links() }}</div>
        @endif
    </div>
</x-layouts.app>
