@props(['product'])

{{-- Catalogue product card: photo with tags, name, SKU, price and add-to-order. --}}
<div {{ $attributes->merge(['class' => 'group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:border-slate-300 hover:shadow-lg hover:shadow-slate-200/60']) }}>
    <a href="{{ route('products.show', $product) }}" class="relative block aspect-[4/3] overflow-hidden bg-slate-50">
        @if ($product->image)
            <img src="{{ $product->image }}" alt="{{ $product->name }}" loading="lazy" class="size-full object-contain p-4 mix-blend-multiply transition duration-300 group-hover:scale-105">
        @else
            <div class="grid size-full place-items-center text-slate-300">
                <span class="flex flex-col items-center gap-1 text-xs"><x-icon name="photo" class="size-8" /> {{ __('No image') }}</span>
            </div>
        @endif
        @if ($product->relationLoaded('tags'))
            <x-tag-badges :tags="$product->tags" class="absolute start-3 top-3 max-w-[75%] [&>span]:shadow-sm" />
        @endif
        @if ($product->stockStatus() !== \App\Models\Product::STOCK_AVAILABLE)
            <x-stock-badge :product="$product" class="absolute end-3 bottom-3 bg-white/90 backdrop-blur" />
        @endif
    </a>
    <div class="flex flex-1 flex-col p-4">
        <a href="{{ route('products.show', $product) }}" class="line-clamp-2 font-semibold text-slate-900 hover:text-brand-600">{{ $product->name }}</a>
        @if ($product->sku)
            <p class="mt-1 font-mono text-xs text-slate-500" dir="ltr">{{ \App\Support\SkuMask::display($product->sku) }}</p>
        @endif
        <div class="mt-auto pt-3">
            <x-money :amount="$product->price" class="text-lg font-bold text-slate-900" />
        </div>
        @unless (auth()->user()?->isAdmin())
            <x-add-to-order :product="$product" compact class="mt-3" />
        @endunless
    </div>
</div>
