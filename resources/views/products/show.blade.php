@php
    $firstCategory = $product->categories->first();
    $fitments = $product->fitments->sortBy(fn ($fitment) => [$fitment->carModel->brand->name, $fitment->carModel->name, $fitment->year_from]);
@endphp

<x-layouts.app :title="$product->name">
    <nav class="mb-5 flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-slate-900">{{ __('Home') }}</a>
        <span>/</span>
        <a href="{{ route('products.index') }}" class="hover:text-slate-900">{{ __('All parts') }}</a>
        @if ($firstCategory)
            <span>/</span>
            <a href="{{ route('products.index', ['category' => $firstCategory->id]) }}" class="hover:text-slate-900">{{ $firstCategory->name }}</a>
        @endif
    </nav>

    <div class="grid items-start gap-6 lg:grid-cols-[1fr_26rem]">
        {{-- Photo and details --}}
        <div class="space-y-6">
            <div class="relative aspect-[4/3] overflow-hidden rounded-2xl border border-slate-200 bg-white">
                @if ($product->image)
                    <img src="{{ $product->image }}" alt="{{ $product->name }}" class="size-full object-contain p-6">
                @else
                    <div class="grid size-full place-items-center text-slate-300">
                        <span class="flex flex-col items-center gap-2 text-sm"><x-icon name="photo" class="size-16" /> {{ __('No image') }}</span>
                    </div>
                @endif
            </div>

            @if ($product->description || $product->categories->isNotEmpty() || $product->brands->isNotEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white p-6">
                    <h2 class="font-semibold text-slate-900">{{ __('Details') }}</h2>
                    @if ($product->description)
                        <p class="mt-3 leading-relaxed whitespace-pre-line text-slate-600">{{ \App\Support\SkuMask::inText($product->description, $product->sku) }}</p>
                    @endif
                    <dl class="mt-4 space-y-2 text-sm">
                        @if ($product->categories->isNotEmpty())
                            <div class="flex flex-wrap items-center gap-2">
                                <dt class="font-semibold text-slate-900">{{ __('Category') }}:</dt>
                                @foreach ($product->categories as $category)
                                    <dd><a href="{{ route('products.index', ['category' => $category->id]) }}" class="rounded-md bg-slate-100 px-2 py-0.5 text-slate-700 hover:bg-slate-200">{{ $category->name }}</a></dd>
                                @endforeach
                            </div>
                        @endif
                        @if ($product->brands->isNotEmpty())
                            <div class="flex flex-wrap items-center gap-2">
                                <dt class="font-semibold text-slate-900">{{ __('Brands') }}:</dt>
                                @foreach ($product->brands as $brand)
                                    <dd>
                                        <a href="{{ route('products.index', ['brand' => $brand->id]) }}" class="inline-flex items-center gap-1.5 rounded-md bg-slate-100 py-0.5 ps-0.5 pe-2 text-slate-700 hover:bg-slate-200">
                                            <x-brand-logo :brand="$brand" class="size-5 rounded" /> {{ $brand->name }}
                                        </a>
                                    </dd>
                                @endforeach
                            </div>
                        @endif
                    </dl>
                </div>
            @endif

            @if ($fitments->isNotEmpty())
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <h2 class="flex items-center gap-2 px-6 pt-5 pb-3 font-semibold text-slate-900"><x-icon name="car" class="size-5" /> {{ __('Part compatibility') }}</h2>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Brand') }}</th>
                                    <th>{{ __('Model') }}</th>
                                    <th>{{ __('Production years') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($fitments as $fitment)
                                    <tr>
                                        <td>
                                            <span class="flex items-center gap-2">
                                                <x-brand-logo :brand="$fitment->carModel->brand" class="size-6 rounded" />
                                                {{ $fitment->carModel->brand->name }}
                                            </span>
                                        </td>
                                        <td class="font-medium text-slate-900">{{ $fitment->carModel->name }}</td>
                                        <td class="text-slate-600 tabular-nums"><span dir="ltr">{{ $fitment->yearsLabel() }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- Buy box --}}
        <div class="space-y-4 lg:sticky lg:top-32">
            {{-- Fitment check --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-4" x-data="{ open: false }">
                @if ($fitsSelectedCar === true)
                    <p class="flex items-center gap-2 text-sm font-semibold text-emerald-700"><x-icon name="check" class="size-5" /> {{ __('Fits your :car', ['car' => $selectedCar]) }}</p>
                @elseif ($fitsSelectedCar === false)
                    <p class="flex items-center gap-2 text-sm font-semibold text-rose-700"><x-icon name="warning" class="size-5" /> {{ __('Does not fit your :car', ['car' => $selectedCar]) }}</p>
                @elseif ($selectedCar)
                    <p class="flex items-center gap-2 text-sm font-medium text-slate-700"><x-icon name="car" class="size-5 text-slate-400" /> {{ __('Your car: :car', ['car' => $selectedCar]) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $product->fitments->isEmpty() ? __('Compatibility is not listed for this part. Contact us if you are not sure.') : __('Choose your model to check the fitment.') }}</p>
                @else
                    <p class="flex items-center gap-2 text-sm font-semibold text-slate-900"><x-icon name="car" class="size-5 text-slate-400" /> {{ __('Select your car') }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ __('To check that this part fits your car.') }}</p>
                @endif
                <button type="button" @click="open = ! open" class="mt-2 text-sm font-medium text-brand-600 hover:text-brand-700">{{ $selectedCar ? __('Change car') : __('Choose car') }}</button>
                <div x-cloak x-show="open" x-transition class="mt-3">
                    <x-vehicle-finder variant="stacked" stay />
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6">
                <div class="flex items-start justify-between gap-3">
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $product->name }}</h1>
                    @if ($product->sku)
                        <span class="shrink-0 rounded-lg border border-slate-300 px-2 py-1 font-mono text-xs text-slate-700" dir="ltr">#{{ \App\Support\SkuMask::display($product->sku) }}</span>
                    @endif
                </div>
                <x-tag-badges :tags="$product->tags" class="mt-3" />

                <x-money :amount="$product->price" class="mt-5 block text-3xl font-bold text-slate-900" />

                <div class="mt-4 flex items-center gap-3 text-sm">
                    <x-stock-badge :product="$product" />
                    @if ($product->stock_quantity !== null)
                        <span class="text-slate-500">{{ __('Available quantity') }}: <span class="font-semibold text-slate-800 tabular-nums">{{ (float) $product->stock_quantity + 0 }}</span></span>
                    @endif
                </div>

                @unless (auth()->user()?->isAdmin())
                    <x-add-to-order :product="$product" class="mt-6 [&_button[type=submit]]:py-3" />
                @endunless

                @if ($product->last_synced_at)
                    <p class="mt-5 flex items-center gap-1.5 border-t border-slate-100 pt-4 text-xs text-slate-400">
                        <x-icon name="clock" class="size-3.5" />
                        {{ __('Last synchronized: :time', ['time' => $product->last_synced_at->diffForHumans()]) }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    @if ($related->isNotEmpty())
        <section class="mt-12">
            <h2 class="mb-5 text-xl font-bold tracking-tight text-slate-900">{{ $product->categories->count() === 1 ? __('More in :category', ['category' => $product->categories->first()->name]) : __('Related products') }}</h2>
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ($related as $item)
                    <x-product-card :product="$item" />
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.app>
