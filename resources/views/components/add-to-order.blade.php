@props(['product', 'compact' => false])

@php
    $user = auth()->user();
    $outOfStock = $product->stockStatus() === \App\Models\Product::STOCK_OUT;
    $max = $product->stock_quantity === null ? 9999 : max(1, (int) floor((float) $product->stock_quantity));
@endphp

@if (! $user)
    <a href="{{ route('login') }}" {{ $attributes->merge(['class' => 'btn-secondary w-full']) }}>{{ __('Login to order') }}</a>
@elseif ($user->isClient() && $user->daftra_client_id)
    @if ($outOfStock)
        <button type="button" disabled {{ $attributes->merge(['class' => 'btn-secondary w-full']) }}>{{ __('Out of Stock') }}</button>
    @else
        <form method="POST" action="{{ route('cart.items.store') }}" x-data="addToOrder({{ $max }})" @submit.prevent="submit($el)" {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <div class="flex items-center rounded-xl ring-1 ring-slate-200">
                <button type="button" @click="qty = Math.max(1, qty - 1)" class="grid size-9 place-items-center text-slate-500 hover:text-slate-900" aria-label="-"><x-icon name="minus" class="size-4" /></button>
                <input type="number" name="quantity" x-model.number="qty" min="1" :max="max" class="w-12 border-0 bg-transparent p-0 text-center text-sm font-semibold tabular-nums focus:ring-0 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                <button type="button" @click="qty = Math.min(max, qty + 1)" class="grid size-9 place-items-center text-slate-500 hover:text-slate-900" aria-label="+"><x-icon name="plus" class="size-4" /></button>
            </div>
            <button type="submit" class="btn-primary flex-1 px-3" :class="added && '!bg-emerald-600'" :disabled="adding">
                <svg x-cloak x-show="adding" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3" /><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg>
                <span x-show="!adding && !added" class="inline-flex"><x-icon name="cart" class="size-4" /></span>
                <span x-cloak x-show="added" class="inline-flex"><x-icon name="check" class="size-4" /></span>
                @unless ($compact)
                    <span x-text="added ? @js(__('Added')) : @js(__('Add to order'))">{{ __('Add to order') }}</span>
                @endunless
            </button>
        </form>
    @endif
@endif
