@php
    $vehicleName = $activeBrand ? trim($activeBrand->name.' '.($activeModel?->name ?? '').' '.($activeModel ? ($filters['year'] ?? '') : '')) : null;
    $heading = $activeCategory?->name ?? __('Car parts');
    // Removing one filter keeps the others.
    $without = fn (string ...$keys) => route('products.index', \Illuminate\Support\Arr::except(request()->query(), [...$keys, 'page']));
    $activeFilters = array_filter([
        $vehicleName ? [$vehicleName, $without('brand', 'model', 'year')] : null,
        $activeCategory ? [$activeCategory->name, $without('category')] : null,
        $activeTag ? [$activeTag->name, $without('tag')] : null,
        ! empty($filters['q']) ? ['“'.$filters['q'].'”', $without('q')] : null,
        ($filters['stock'] ?? null) === 'in_stock' ? [__('Available only'), $without('stock')] : null,
    ]);
@endphp

<x-layouts.app :title="$heading">
    <nav class="mb-2 flex items-center gap-1.5 text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-slate-900">{{ __('Home') }}</a>
        <span>/</span>
        <a href="{{ route('products.index') }}" class="hover:text-slate-900">{{ __('All parts') }}</a>
        @if ($activeCategory)
            <span>/</span>
            <span class="text-slate-700">{{ $activeCategory->name }}</span>
        @endif
    </nav>
    <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
        {{ $heading }}
        @if ($vehicleName)
            <span class="text-slate-400">·</span> <span class="text-brand-600">{{ $vehicleName }}</span>
        @endif
    </h1>

    {{-- Car selector --}}
    @if (\App\Models\Brand::forVehicleFinder()->isNotEmpty())
        <div class="mt-5 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 lg:flex-row lg:items-center">
            <div class="shrink-0 lg:w-56">
                <p class="flex items-center gap-2 text-sm font-semibold text-slate-900"><x-icon name="car" class="size-4" /> {{ __('Choose your car to see parts') }}</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ __('The year is optional.') }}</p>
            </div>
            <x-vehicle-finder variant="bar" class="flex-1" :brand="$filters['brand'] ?? ''" :model="$filters['model'] ?? ''" :year="$filters['year'] ?? ''" />
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-[16rem_1fr]" x-data="{ filtersOpen: false }">
        {{-- Filters --}}
        <aside>
            <button type="button" @click="filtersOpen = ! filtersOpen" class="btn-secondary w-full lg:hidden">
                <x-icon name="sort" class="size-4" /> {{ __('Filters') }}
                @if (count($activeFilters))
                    <span class="rounded-full bg-brand-600 px-1.5 text-xs text-white">{{ count($activeFilters) }}</span>
                @endif
            </button>

            {{-- Compact so it fits on screen; long lists collapse behind "Show more" (the box only scrolls if those are opened on a small screen). --}}
            <form id="filters" method="GET" action="{{ route('products.index') }}" class="mt-3 hidden rounded-2xl border border-slate-200 bg-white p-3.5 lg:sticky lg:top-32 lg:mt-0 lg:block lg:max-h-[calc(100vh-9rem)] lg:overflow-y-auto lg:overscroll-contain [scrollbar-width:thin]" :class="filtersOpen && '!block'">
                @foreach (['q', 'model', 'year'] as $keep)
                    @if (! empty($filters[$keep]))
                        <input type="hidden" name="{{ $keep }}" value="{{ $filters[$keep] }}" data-vehicle="{{ $keep === 'q' ? '' : '1' }}">
                    @endif
                @endforeach

                <p class="text-sm font-semibold text-slate-900">{{ __('Filters') }}</p>

                <label class="mt-2.5 flex cursor-pointer items-center justify-between gap-3 border-b border-slate-100 pb-2.5 text-[13px] font-medium text-slate-700">
                    {{ __('Available only') }}
                    <span class="relative inline-flex">
                        <input type="checkbox" name="stock" value="in_stock" @checked(($filters['stock'] ?? null) === 'in_stock') onchange="this.form.submit()" class="peer sr-only">
                        <span class="h-5 w-9 rounded-full bg-slate-200 transition peer-checked:bg-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500"></span>
                        <span class="absolute start-0.5 top-0.5 size-4 rounded-full bg-white shadow transition peer-checked:translate-x-4 rtl:peer-checked:-translate-x-4"></span>
                    </span>
                </label>

                @if ($categories->isNotEmpty())
                    <fieldset class="border-b border-slate-100 py-2.5" x-data="{ more: false }">
                        <legend class="contents"><span class="mb-1 block text-[13px] font-semibold text-slate-900">{{ __('Category') }}</span></legend>
                        @foreach (collect([(object) ['id' => '', 'name' => __('All categories')]])->concat($categories) as $category)
                            <label @if ($loop->index > 5 && (string) $category->id !== (string) ($filters['category'] ?? '')) x-show="more" x-cloak @endif
                                class="flex cursor-pointer items-center gap-2 rounded-md px-1 py-1 text-[13px] text-slate-600 hover:text-slate-900">
                                <input type="radio" name="category" value="{{ $category->id }}" @checked((string) $category->id === (string) ($filters['category'] ?? '')) onchange="this.form.submit()" class="size-3.5 border-slate-300 text-brand-600 focus:ring-brand-500">
                                {{ $category->name }}
                            </label>
                        @endforeach
                        @if ($categories->count() > 5)
                            <button type="button" x-show="! more" @click="more = true" class="mt-0.5 px-1 text-xs font-medium text-brand-600 hover:text-brand-700">{{ __('Show more (:count)', ['count' => $categories->count() - 5]) }}</button>
                        @endif
                    </fieldset>
                @endif

                @if ($brands->isNotEmpty())
                    {{-- Changing the brand drops the model/year of the previous car. --}}
                    <fieldset class="border-b border-slate-100 py-2.5" x-data="{ more: false }">
                        <legend class="contents"><span class="mb-1 block text-[13px] font-semibold text-slate-900">{{ __('Brand') }}</span></legend>
                        @foreach (collect([(object) ['id' => '', 'name' => __('All brands')]])->concat($brands) as $brand)
                            <label @if ($loop->index > 4 && (string) $brand->id !== (string) ($filters['brand'] ?? '')) x-show="more" x-cloak @endif
                                class="flex cursor-pointer items-center gap-2 rounded-md px-1 py-1 text-[13px] text-slate-600 hover:text-slate-900">
                                <input type="radio" name="brand" value="{{ $brand->id }}" @checked((string) $brand->id === (string) ($filters['brand'] ?? ''))
                                    onchange="this.form.querySelectorAll('[data-vehicle=&quot;1&quot;]').forEach(input => input.remove()); this.form.submit()"
                                    class="size-3.5 border-slate-300 text-brand-600 focus:ring-brand-500">
                                @if ($brand instanceof \App\Models\Brand)
                                    <x-brand-logo :brand="$brand" class="size-5 rounded ring-1 ring-slate-200" />
                                    <span class="flex-1 truncate">{{ $brand->name }}</span>
                                    <span class="text-[11px] text-slate-400 tabular-nums">{{ number_format($brand->products_count) }}</span>
                                @else
                                    {{ $brand->name }}
                                @endif
                            </label>
                        @endforeach
                        @if ($brands->count() > 4)
                            <button type="button" x-show="! more" @click="more = true" class="mt-0.5 px-1 text-xs font-medium text-brand-600 hover:text-brand-700">{{ __('Show more (:count)', ['count' => $brands->count() - 4]) }}</button>
                        @endif
                    </fieldset>
                @endif

                @if ($tags->isNotEmpty())
                    <fieldset class="pt-2.5">
                        <legend class="contents"><span class="mb-1 block text-[13px] font-semibold text-slate-900">{{ __('Type') }}</span></legend>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach (collect([(object) ['id' => '', 'name' => __('All')]])->concat($tags) as $tag)
                                <label class="cursor-pointer">
                                    <input type="radio" name="tag" value="{{ $tag->id }}" @checked((string) $tag->id === (string) ($filters['tag'] ?? '')) onchange="this.form.submit()" class="peer sr-only">
                                    <span class="inline-flex rounded-full border border-slate-200 px-2.5 py-1 text-xs text-slate-600 transition peer-checked:border-brand-200 peer-checked:bg-brand-50 peer-checked:font-medium peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 hover:border-slate-300">{{ $tag->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <noscript><button type="submit" class="btn-primary mt-4 w-full">{{ __('Apply') }}</button></noscript>
            </form>
        </aside>

        {{-- Results --}}
        <section class="min-w-0">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-500">{{ trans_choice(':count product|:count products', $products->total(), ['count' => number_format($products->total())]) }}</p>
                @if ($clientsCanSort || auth()->user()?->isAdmin())
                    <label class="flex items-center gap-2 text-sm text-slate-500">
                        {{ __('Sort by') }}
                        <select name="sort" form="filters" onchange="this.form.submit()" class="rounded-lg border-slate-200 py-1.5 ps-3 pe-8 text-sm text-slate-900 focus:border-brand-500 focus:ring-brand-100">
                            @foreach (\App\Models\Product::SORTS as $value => $label)
                                <option value="{{ $value }}" @selected($sort === $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
            </div>

            @if (count($activeFilters))
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    @foreach ($activeFilters as [$label, $removeUrl])
                        <a href="{{ $removeUrl }}" class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-sm text-slate-700 ring-1 ring-slate-200 hover:ring-slate-300">
                            {{ $label }} <x-icon name="x" class="size-3.5 text-slate-400" />
                        </a>
                    @endforeach
                    <a href="{{ route('products.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">{{ __('Clear filters') }}</a>
                </div>
            @endif

            @if ($products->isEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white">
                    <x-empty-state icon="cube" :title="__('No products found')" :description="__('Try a different search term or category.')">
                        @if (count($activeFilters))
                            <a href="{{ route('products.index') }}" class="btn-secondary mt-5">{{ __('Clear filters') }}</a>
                        @endif
                    </x-empty-state>
                </div>
            @else
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>

                <div class="mt-8">{{ $products->links() }}</div>
            @endif

            @if ($lastSyncedAt)
                <p class="mt-6 inline-flex items-center gap-1.5 text-xs text-slate-400">
                    <x-icon name="clock" class="size-3.5" />
                    {{ __('Last synchronized: :time', ['time' => \Illuminate\Support\Carbon::parse($lastSyncedAt)->diffForHumans()]) }}
                </p>
            @endif
        </section>
    </div>
</x-layouts.app>
