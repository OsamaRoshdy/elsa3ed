<x-layouts.app :title="__('Orders')">
    <x-page-header :title="__('Orders')" :subtitle="__('Orders you have sent and their status.')">
        <a href="{{ route('cart.show') }}" class="btn-primary"><x-icon name="cart" class="size-4" /> {{ __('My Order') }}</a>
    </x-page-header>

    @if (! $client)
        @include('portal.not-linked')
    @else
        <div class="card overflow-hidden">
            @if ($orders->isEmpty())
                <x-empty-state icon="clipboard" :title="__('No orders yet')" :description="__('Browse the products and click “Add to order”.')">
                    <a href="{{ route('products.index') }}" class="btn-primary mt-5">{{ __('Browse products') }}</a>
                </x-empty-state>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{{ __('Order #') }}</th>
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
                                    <td>{{ $order->submitted_at?->translatedFormat('d M Y') }}</td>
                                    <td class="tabular-nums">{{ $order->items_count }}</td>
                                    <td><x-money :amount="$order->estimated_total" /></td>
                                    <td><x-order-status :status="$order->status" /></td>
                                    <td class="text-end">
                                        <a href="{{ route('orders.show', $order) }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:text-brand-700">{{ __('View') }} <x-icon name="chevron" class="size-4 rtl:-scale-x-100" /></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">{{ $orders->links() }}</div>
            @endif
        </div>
    @endif
</x-layouts.app>
