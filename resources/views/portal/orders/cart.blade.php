<x-layouts.app :title="__('My Order')">
    <x-page-header :title="__('My Order')" :subtitle="__('Add products, review quantities, then send your order. We will confirm it and issue the invoice.')">
        <a href="{{ route('products.index') }}" class="btn-secondary"><x-icon name="plus" class="size-4" /> {{ __('Add products') }}</a>
    </x-page-header>

    @if (! $client)
        @include('portal.not-linked')
    @elseif (! $order || $order->items->isEmpty())
        <div class="card">
            <x-empty-state icon="cart" :title="__('Your order is empty')" :description="__('Browse the products and click “Add to order”.')">
                <a href="{{ route('products.index') }}" class="btn-primary mt-5">{{ __('Browse products') }}</a>
            </x-empty-state>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="card overflow-hidden lg:col-span-2">
                <ul class="divide-y divide-slate-100">
                    @foreach ($order->items as $item)
                        @php($unavailable = ! $item->product || ! $item->product->is_active)
                        <li class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
                            <div class="flex min-w-0 flex-1 items-center gap-4">
                                <div class="size-16 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                                    @if ($item->product?->image)
                                        <img src="{{ $item->product->image }}" alt="" class="size-full object-cover">
                                    @else
                                        <div class="grid size-full place-items-center text-slate-300"><x-icon name="photo" /></div>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-slate-900">{{ $item->product_name }}</p>
                                    <p class="text-xs text-slate-400" dir="ltr">{{ $item->sku ? 'SKU: '.\App\Support\SkuMask::display($item->sku) : '' }}</p>
                                    <p class="mt-1 text-sm text-slate-500"><x-money :amount="$item->price" /> × {{ $item->quantity }}</p>
                                    @if ($unavailable)
                                        <p class="mt-1 text-xs font-medium text-rose-600">{{ __('No longer available') }}</p>
                                    @elseif ($item->product->stock_quantity !== null && $item->quantity >= (float) $item->product->stock_quantity)
                                        <p class="mt-1 text-xs font-medium text-amber-600">{{ __('Maximum available quantity') }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-4 sm:justify-end">
                                <form method="POST" action="{{ route('cart.items.update', $item) }}" class="flex items-center rounded-xl ring-1 ring-slate-200">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" name="quantity" value="{{ max(1, $item->quantity - 1) }}" class="grid size-9 place-items-center text-slate-500 hover:text-slate-900" @disabled($item->quantity <= 1)><x-icon name="minus" class="size-4" /></button>
                                    <span class="w-10 text-center text-sm font-semibold tabular-nums">{{ $item->quantity }}</span>
                                    <button type="submit" name="quantity" value="{{ $item->quantity + 1 }}" class="grid size-9 place-items-center text-slate-500 hover:text-slate-900"><x-icon name="plus" class="size-4" /></button>
                                </form>
                                <x-money :amount="$item->lineTotal()" class="w-28 text-end font-semibold text-slate-900" />
                                <form method="POST" action="{{ route('cart.items.destroy', $item) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600" title="{{ __('Remove') }}"><x-icon name="trash" class="size-4" /></button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <form method="POST" action="{{ route('cart.submit') }}" class="card h-fit space-y-5 p-6" x-data="{ sending: false }" @submit="sending = true">
                @csrf
                <h2 class="font-semibold text-slate-900">{{ __('Order summary') }}</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-slate-500">{{ __('Items') }}</dt>
                        <dd class="font-medium tabular-nums">{{ $order->items->sum('quantity') }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-slate-100 pt-3 text-base">
                        <dt class="font-semibold text-slate-900">{{ __('Estimated total') }}</dt>
                        <dd class="font-bold text-slate-900"><x-money :amount="$order->estimated_total" /></dd>
                    </div>
                </dl>
                <p class="rounded-xl bg-slate-50 p-3 text-xs text-slate-500">{{ __('Prices are estimates. The final amount will appear on your invoice after we confirm the order.') }}</p>

                <div>
                    <label for="notes" class="label">{{ __('Notes (optional)') }}</label>
                    <textarea id="notes" name="notes" rows="3" class="input" placeholder="{{ __('Delivery details, car model, anything we should know...') }}">{{ old('notes', $order->notes) }}</textarea>
                    @error('notes') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn-primary w-full py-3" :disabled="sending">
                    <span x-show="!sending">{{ __('Send order') }}</span>
                    <span x-cloak x-show="sending">{{ __('Sending...') }}</span>
                </button>
            </form>
        </div>
    @endif
</x-layouts.app>
