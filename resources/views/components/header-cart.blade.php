@props(['count' => 0])

{{-- Header mini-cart for client users. Count is live (Alpine store "cart"); items load when opened. --}}
<div
    {{ $attributes->merge(['class' => 'relative']) }}
    x-data="{ open: false }"
    x-init="$store.cart.summaryUrl = @js(route('cart.summary')); $store.cart.count = {{ (int) $count }}"
    @keydown.escape.window="open = false"
    @click.outside="open = false"
>
    <button
        type="button"
        @click="open = !open; if (open) $store.cart.refresh()"
        class="relative grid size-10 place-items-center rounded-xl text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
        :class="$store.cart.bump && 'scale-110 text-brand-600'"
        aria-label="{{ __('My Order') }}"
    >
        <x-icon name="cart" class="size-6" />
        <span
            x-cloak
            x-show="$store.cart.count > 0"
            x-text="$store.cart.count > 99 ? '99+' : $store.cart.count"
            class="absolute -top-0.5 -end-0.5 grid h-5 min-w-5 place-items-center rounded-full bg-amber-500 px-1 text-[11px] font-bold text-white ring-2 ring-white tabular-nums"
            :class="$store.cart.bump && 'animate-bounce'"
        ></span>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.origin.top
        class="absolute end-0 top-12 z-40 w-[22rem] max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-300/40"
    >
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <p class="font-semibold text-slate-900">{{ __('My Order') }}</p>
            <span class="text-xs text-slate-500" x-show="$store.cart.count > 0" x-text="$store.cart.count + ' ' + @js(__('items'))"></span>
        </div>

        <div x-show="$store.cart.loading && ! $store.cart.loaded" class="px-4 py-8 text-center text-sm text-slate-400">{{ __('Loading...') }}</div>

        <div x-show="$store.cart.loaded && $store.cart.items.length === 0" class="px-4 py-8 text-center">
            <p class="text-sm font-medium text-slate-900">{{ __('Your order is empty') }}</p>
            <a href="{{ route('products.index') }}" class="mt-3 inline-flex text-sm font-medium text-brand-600 hover:text-brand-700">{{ __('Browse products') }}</a>
        </div>

        <ul x-show="$store.cart.items.length > 0" class="max-h-80 divide-y divide-slate-100 overflow-y-auto">
            <template x-for="item in $store.cart.items" :key="item.id">
                <li class="flex items-center gap-3 px-4 py-3">
                    <div class="size-11 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                        <template x-if="item.image"><img :src="item.image" alt="" class="size-full object-cover"></template>
                        <template x-if="! item.image"><div class="grid size-full place-items-center text-slate-300"><x-icon name="photo" class="size-4" /></div></template>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-slate-900" x-text="item.name"></p>
                        <p class="text-xs text-slate-500">× <span class="tabular-nums" x-text="item.quantity"></span></p>
                    </div>
                    <p class="shrink-0 text-sm font-semibold text-slate-900 tabular-nums" x-text="item.line_total"></p>
                </li>
            </template>
        </ul>

        <div x-show="$store.cart.items.length > 0" class="space-y-3 border-t border-slate-100 bg-slate-50/60 p-4">
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-500">{{ __('Estimated total') }}</span>
                <span class="font-bold text-slate-900 tabular-nums" x-text="$store.cart.total"></span>
            </div>
            <a href="{{ route('cart.show') }}" class="btn-primary w-full">{{ __('Review & send order') }}</a>
        </div>
    </div>
</div>
