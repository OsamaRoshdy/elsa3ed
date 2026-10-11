@props(['product'])

@php
    $status = $product->stockStatus();
    $styles = [
        'available' => ['bg-emerald-50 text-emerald-700 ring-emerald-600/20', 'bg-emerald-500', __('Available')],
        'low' => ['bg-amber-50 text-amber-700 ring-amber-600/20', 'bg-amber-500', __('Low Stock')],
        'out' => ['bg-rose-50 text-rose-700 ring-rose-600/20', 'bg-rose-500', __('Out of Stock')],
    ][$status];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {$styles[0]}"]) }}>
    <span class="size-1.5 rounded-full {{ $styles[1] }}"></span>
    {{ $styles[2] }}
</span>
