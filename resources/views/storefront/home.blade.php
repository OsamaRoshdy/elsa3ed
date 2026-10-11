<x-layouts.app :title="__('Car parts')" bleed>
    {{-- Hero with the car finder --}}
    <section class="relative overflow-hidden bg-slate-100">
        {{-- Background photo with a light overlay so the heading stays readable; fades into the page at the bottom. --}}
        <img src="{{ asset('images/hero-aisle-light.jpg') }}" alt="" aria-hidden="true" fetchpriority="high" class="pointer-events-none absolute inset-0 size-full object-cover object-center">
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-white/70 via-white/35 to-slate-50"></div>
        <div class="relative mx-auto max-w-7xl px-4 pt-14 pb-28 text-center sm:px-6 sm:pt-24 sm:pb-36 lg:px-8">
            <h1 class="mx-auto max-w-3xl text-3xl font-bold tracking-tight text-slate-900 sm:text-5xl">{{ __('Genuine and aftermarket car parts') }}</h1>
            <p class="mx-auto mt-3 max-w-xl text-slate-600">{{ __('Choose your car to find the parts that fit it.') }}</p>

            <div class="mx-auto mt-8 max-w-2xl rounded-2xl bg-white p-5 text-start shadow-xl shadow-slate-300/40 ring-1 ring-slate-200 sm:p-6" x-data="{ tab: 'car' }">
                <div class="mb-4 flex gap-5 border-b border-slate-100 text-sm font-medium">
                    <button type="button" @click="tab = 'car'" class="-mb-px border-b-2 pb-2.5 transition" :class="tab === 'car' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-900'">{{ __('Search by car') }}</button>
                    <button type="button" @click="tab = 'part'" class="-mb-px border-b-2 pb-2.5 transition" :class="tab === 'part' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-900'">{{ __('By part name or number') }}</button>
                </div>

                <div x-show="tab === 'car'">
                    @if (\App\Models\Brand::forVehicleFinder()->isEmpty())
                        <p class="py-4 text-center text-sm text-slate-500">{{ __('Browse all our parts below.') }}</p>
                    @else
                        <x-vehicle-finder variant="hero" />
                    @endif
                </div>
                <form x-cloak x-show="tab === 'part'" method="GET" action="{{ route('products.index') }}" class="grid gap-3">
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute inset-y-0 start-3.5 my-auto size-4 text-slate-400" />
                        <input type="search" name="q" required placeholder="{{ __('e.g. brake pads or 58101-2HA10') }}" class="input py-3 ps-10">
                    </div>
                    <button type="submit" class="btn-primary py-3 text-base">{{ __('Search') }}</button>
                </form>
            </div>
        </div>
    </section>

    {{-- Stats --}}
    <div class="relative mx-auto -mt-12 max-w-7xl px-4 sm:px-6 lg:px-8">
        <dl class="grid grid-cols-2 divide-slate-100 overflow-hidden rounded-2xl bg-white shadow-lg shadow-slate-200/60 ring-1 ring-slate-200 sm:grid-cols-4 sm:divide-x rtl:sm:divide-x-reverse">
            @foreach ([
                [$stats['products'], __('Parts in the catalogue')],
                [$stats['categories'], __('Categories')],
                [$stats['brands'], __('Car brands')],
                [$stats['models'], __('Car models')],
            ] as [$value, $label])
                <div class="flex flex-col-reverse px-4 py-5 text-center">
                    <dt class="mt-1 text-sm text-slate-500">{{ $label }}</dt>
                    <dd class="font-mono text-2xl font-bold text-slate-900 tabular-nums sm:text-3xl">{{ number_format($value) }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    <div class="mx-auto max-w-7xl space-y-16 px-4 pt-16 sm:px-6 lg:px-8">
        {{-- Brands slider: swipe/scroll, arrows, auto-advances (paused on hover/focus, off for reduced motion).
             No CSS scroll-snap: Chrome ignores scrollBy() on a snapped right-to-left track. --}}
        @if ($brands->isNotEmpty())
            <section
                x-data="{
                    atStart: true,
                    atEnd: false,
                    paused: false,
                    // scrollLeft is negative in RTL, so work with distances from the start.
                    direction() { return getComputedStyle($refs.track).direction === 'rtl' ? -1 : 1 },
                    update() {
                        const track = $refs.track;
                        const offset = Math.abs(track.scrollLeft);
                        this.atStart = offset <= 1;
                        this.atEnd = offset + track.clientWidth >= track.scrollWidth - 1;
                    },
                    slide(step) {
                        const track = $refs.track;
                        track.scrollBy({ left: step * this.direction() * Math.max(track.clientWidth * 0.8, 160), behavior: 'smooth' });
                    },
                    autoplay() {
                        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                        setInterval(() => {
                            if (this.paused || document.hidden) return;
                            this.atEnd ? $refs.track.scrollTo({ left: 0, behavior: 'smooth' }) : this.slide(1);
                        }, 3500);
                    },
                }"
                x-init="update(); autoplay()"
                @mouseenter="paused = true"
                @mouseleave="paused = false"
                @focusin="paused = true"
                @focusout="paused = false"
                aria-roledescription="carousel"
            >
                <div class="mb-6 flex items-end justify-between gap-4">
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ __('Parts for :count car brands', ['count' => $brands->count()]) }}</h2>
                    <div class="flex shrink-0 gap-2">
                        <button type="button" @click="slide(-1)" :disabled="atStart" class="grid size-10 place-items-center rounded-full border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40" aria-label="{{ __('Previous') }}">
                            <x-icon name="chevron" class="size-5 rotate-180 rtl:rotate-0" />
                        </button>
                        <button type="button" @click="slide(1)" :disabled="atEnd" class="grid size-10 place-items-center rounded-full border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40" aria-label="{{ __('Next') }}">
                            <x-icon name="chevron" class="size-5 rtl:rotate-180" />
                        </button>
                    </div>
                </div>

                <div x-ref="track" @scroll.debounce.50ms="update()" @resize.window.debounce.100ms="update()"
                    class="flex gap-3 overflow-x-auto scroll-smooth pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($brands as $brand)
                        <a href="{{ route('products.index', ['brand' => $brand->id]) }}" title="{{ $brand->name }}"
                            class="group flex w-32 shrink-0 flex-col items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-5 transition hover:border-slate-300 hover:shadow-md hover:shadow-slate-200/60 sm:w-36">
                            <x-brand-logo :brand="$brand" class="h-14 w-20 grayscale transition group-hover:grayscale-0 [&>span]:text-lg" />
                            <span class="text-sm font-medium text-slate-600 group-hover:text-slate-900">{{ $brand->name }}</span>
                            <span class="text-xs text-slate-400">{{ trans_choice(':count product|:count products', $brand->products_count, ['count' => number_format($brand->products_count)]) }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Categories --}}
        @if ($categories->isNotEmpty())
            <section>
                <div class="mb-6 flex items-end justify-between gap-4">
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ __('Start from a category') }}</h2>
                    <a href="{{ route('products.index') }}" class="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-slate-600 hover:text-slate-900">{{ __('View all') }} <x-icon name="chevron" class="size-4 rtl:rotate-180" /></a>
                </div>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                    @foreach ($categories as $category)
                        <a href="{{ route('products.index', ['category' => $category->id]) }}" class="group flex flex-col items-center rounded-2xl p-3 text-center transition hover:bg-white hover:shadow-md hover:shadow-slate-200/60">
                            <x-category-image :category="$category" class="aspect-square w-full rounded-2xl bg-white ring-1 ring-slate-100 transition group-hover:ring-slate-200 [&>img]:p-4 [&>img]:transition [&>img]:duration-300 group-hover:[&>img]:scale-105" />
                            <span class="mt-3 font-semibold text-slate-800">{{ $category->name }}</span>
                            <span class="text-xs text-slate-500">{{ trans_choice(':count product|:count products', $category->products_count, ['count' => number_format($category->products_count)]) }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Featured parts --}}
        @if ($featured->isNotEmpty())
            <section>
                <div class="mb-6 flex items-end justify-between gap-4">
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ __('Latest parts') }}</h2>
                    <a href="{{ route('products.index') }}" class="inline-flex shrink-0 items-center gap-1 text-sm font-medium text-slate-600 hover:text-slate-900">{{ __('View all') }} <x-icon name="chevron" class="size-4 rtl:rotate-180" /></a>
                </div>
                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    @foreach ($featured as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Call to action --}}
        <section class="flex flex-col items-start justify-between gap-5 rounded-3xl bg-brand-600 px-6 py-8 text-white sm:flex-row sm:items-center sm:px-10">
            <div>
                <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ __('Don\'t know the part name?') }}</h2>
                <p class="mt-1 text-sm text-white/80">{{ __('Browse all parts and categories to easily find what you need.') }}</p>
            </div>
            <a href="{{ route('products.index') }}" class="btn shrink-0 bg-white text-brand-700 hover:bg-white/90">{{ __('Browse parts') }} <x-icon name="chevron" class="size-4 rtl:rotate-180" /></a>
        </section>

    </div>
</x-layouts.app>
