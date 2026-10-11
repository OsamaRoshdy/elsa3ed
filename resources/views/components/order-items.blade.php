@props(['order', 'showStock' => false])

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>{{ __('Product') }}</th>
                <th>SKU</th>
                @if ($showStock)
                    <th>{{ __('Current stock') }}</th>
                @endif
                <th>{{ __('Price') }}</th>
                <th>{{ __('Quantity') }}</th>
                <th>{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td class="font-medium text-slate-900">{{ $item->product_name }}</td>
                    <td dir="ltr" class="text-start font-mono text-xs">{{ \App\Support\SkuMask::display($item->sku) ?? '—' }}</td>
                    @if ($showStock)
                        <td>
                            @if ($item->product)
                                <span class="me-1 tabular-nums">{{ $item->product->stock_quantity === null ? '—' : (float) $item->product->stock_quantity + 0 }}</span>
                                <x-stock-badge :product="$item->product" />
                            @else
                                <span class="text-xs text-rose-600">{{ __('No longer available') }}</span>
                            @endif
                        </td>
                    @endif
                    <td><x-money :amount="$item->price" /></td>
                    <td class="font-semibold tabular-nums">{{ $item->quantity }}</td>
                    <td class="font-semibold"><x-money :amount="$item->lineTotal()" /></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="{{ $showStock ? 5 : 4 }}" class="border-t border-slate-200 px-4 py-3.5 text-end font-semibold text-slate-900">{{ __('Estimated total') }}</td>
                <td class="border-t border-slate-200 px-4 py-3.5 font-bold text-slate-900"><x-money :amount="$order->estimated_total" /></td>
            </tr>
        </tfoot>
    </table>
</div>
