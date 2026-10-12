{{-- Storefront layout for clients, guests and admins previewing the store (included by components.layouts.app). --}}
@php
    $isClient = (bool) $user?->isClient();
    $hasCart = $isClient && $user->daftra_client_id;
    $vehicleLabel = $garage->label();
    $accountLinks = $user?->isAdmin() ? [
        ['admin.dashboard', 'admin.*', 'home', __('Dashboard')],
    ] : [
        ['dashboard', 'dashboard', 'home', __('Dashboard')],
        ['cart.show', 'cart.*', 'cart', __('My Order')],
        ['orders.index', 'orders.*', 'clipboard', __('Orders')],
        ['invoices.index', 'invoices.*', 'document', __('Invoices')],
        ['payments.index', 'payments.*', 'banknotes', __('Payments')],
        ['account', 'account', 'user', __('My Account')],
    ];
    $onAccountPage = $isClient && request()->routeIs(...array_column($accountLinks, 1));
@endphp

<div x-data="{ menuOpen: false }" class="flex min-h-full flex-col bg-slate-50">
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white">
        <div class="mx-auto flex h-16 max-w-7xl items-center gap-3 px-4 sm:px-6 lg:px-8">
            <button type="button" @click="menuOpen = true" class="-ms-2 p-2 text-slate-600 lg:hidden" aria-label="{{ __('Menu') }}"><x-icon name="menu" class="size-6" /></button>

            <a href="{{ route('home') }}" class="shrink-0"><x-logo :tagline="false" class="[&_svg]:size-9" /></a>

            <form method="GET" action="{{ route('products.index') }}" class="relative ms-2 hidden max-w-xl flex-1 md:block" role="search">
                <x-icon name="search" class="pointer-events-none absolute inset-y-0 start-3.5 my-auto size-4 text-slate-400" />
                <input type="search" name="q" value="{{ request()->routeIs('products.index') ? request('q') : '' }}" placeholder="{{ __('Search by part name or number') }}"
                    class="w-full rounded-xl border-0 bg-slate-100 py-2.5 ps-10 pe-4 text-sm text-slate-900 placeholder:text-slate-500 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </form>

            <div class="flex-1"></div>

            {{-- Select your car --}}
            <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                <button type="button" @click="open = ! open" class="flex max-w-[11rem] items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 sm:max-w-[16rem]">
                    <x-icon name="car" class="size-4 shrink-0 text-slate-500" />
                    <span class="truncate">{{ $vehicleLabel ?? __('Select your car') }}</span>
                    <x-icon name="chevron" class="size-3.5 shrink-0 rotate-90 text-slate-400" />
                </button>
                <div x-cloak x-show="open" x-transition.origin.top class="absolute end-0 top-12 z-40 w-[22rem] max-w-[calc(100vw-2rem)] rounded-2xl border border-slate-200 bg-white p-4 shadow-xl shadow-slate-300/40">
                    <p class="font-semibold text-slate-900">{{ __('Select your car') }}</p>
                    <p class="mt-0.5 mb-3 text-xs text-slate-500">{{ __('We will show the parts that fit it.') }}</p>
                    <x-vehicle-finder variant="stacked" />
                    @if ($vehicleLabel)
                        <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-100 pt-3 text-sm">
                            <a href="{{ route('products.index', $garage->query()) }}" class="font-medium text-brand-600 hover:text-brand-700">{{ __('Parts for my :car', ['car' => $vehicleLabel]) }}</a>
                            <form method="POST" action="{{ route('vehicle.destroy') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-500 hover:text-rose-600">{{ __('Clear') }}</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Account --}}
            @if ($user)
                <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                    <button type="button" @click="open = ! open" class="grid size-10 place-items-center rounded-xl text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" aria-label="{{ __('My Account') }}">
                        <x-icon name="user" class="size-6" />
                    </button>
                    <div x-cloak x-show="open" x-transition.origin.top class="absolute end-0 top-12 z-40 w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-300/40">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                            <p class="truncate text-xs text-slate-500" dir="ltr">{{ $user->email }}</p>
                        </div>
                        <nav class="p-2">
                            @foreach ($accountLinks as [$route, $pattern, $icon, $label])
                                <a href="{{ route($route) }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition', 'bg-brand-50 text-brand-700' => request()->routeIs($pattern), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs($pattern)])>
                                    <x-icon :name="$icon" class="size-4" /> {{ $label }}
                                </a>
                            @endforeach
                        </nav>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100 p-2">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-rose-50 hover:text-rose-700">
                                <x-icon name="logout" class="size-4 rtl:-scale-x-100" /> {{ __('Logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="grid size-10 place-items-center rounded-xl text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 sm:hidden" aria-label="{{ __('Login') }}"><x-icon name="user" class="size-6" /></a>
                <a href="{{ route('login') }}" class="btn-primary hidden sm:inline-flex">{{ __('Login') }}</a>
            @endif

            @if ($hasCart)
                <x-header-cart :count="$navCounts['cart'] ?? 0" />
            @endif
        </div>

        {{-- Search on small screens --}}
        <form method="GET" action="{{ route('products.index') }}" class="px-4 pb-3 md:hidden" role="search">
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute inset-y-0 start-3.5 my-auto size-4 text-slate-400" />
                <input type="search" name="q" value="{{ request()->routeIs('products.index') ? request('q') : '' }}" placeholder="{{ __('Search by part name or number') }}"
                    class="w-full rounded-xl border-0 bg-slate-100 py-2.5 ps-10 pe-4 text-sm text-slate-900 placeholder:text-slate-500 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>
        </form>

        {{-- Category / brand menu --}}
        <nav class="hidden border-t border-slate-100 lg:block">
            <div class="mx-auto flex h-11 max-w-7xl items-center gap-1 px-4 text-sm font-medium sm:px-6 lg:px-8">
                <a href="{{ route('products.index') }}" @class(['rounded-lg px-3 py-1.5 transition', 'text-brand-600' => request()->routeIs('products.*'), 'text-slate-700 hover:bg-slate-50' => ! request()->routeIs('products.*')])>{{ __('All parts') }}</a>

                @foreach ([['categories', __('Categories'), $storeCategories], ['brands', __('Brands'), $storeBrands]] as [$menu, $menuLabel, $menuItems])
                    @if ($menuItems->isNotEmpty())
                        <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false" @keydown.escape.window="open = false">
                            <button type="button" @click="open = ! open" class="flex items-center gap-1 rounded-lg px-3 py-1.5 text-slate-700 transition hover:bg-slate-50">
                                {{ $menuLabel }} <x-icon name="chevron" class="size-3.5 rotate-90 text-slate-400" />
                            </button>
                            <div x-cloak x-show="open" x-transition.origin.top class="absolute start-0 top-full z-40 pt-2">
                                <div class="grid w-[36rem] grid-cols-3 gap-1 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl shadow-slate-300/40">
                                    @foreach ($menuItems as $item)
                                        <a href="{{ route('products.index', [$menu === 'categories' ? 'category' : 'brand' => $item->id]) }}" class="flex items-center gap-2.5 rounded-xl px-2.5 py-2 text-slate-700 transition hover:bg-slate-50 hover:text-slate-900">
                                            @if ($menu === 'brands')
                                                <x-brand-logo :brand="$item" class="size-7 rounded-md ring-1 ring-slate-200" />
                                            @else
                                                <x-category-image :category="$item" class="size-7 rounded-md" />
                                            @endif
                                            <span class="truncate">{{ $item->name }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach

                <div class="flex-1"></div>
                <x-language-switcher />
            </div>
        </nav>
    </header>

    {{-- Mobile menu --}}
    <div x-cloak x-show="menuOpen" x-transition.opacity @click="menuOpen = false" class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm lg:hidden"></div>
    <aside x-cloak x-show="menuOpen" x-transition:enter="transition duration-200" x-transition:enter-start="{{ $isRtl ? 'translate-x-full' : '-translate-x-full' }}" x-transition:leave="transition duration-150" x-transition:leave-end="{{ $isRtl ? 'translate-x-full' : '-translate-x-full' }}"
        class="fixed inset-y-0 start-0 z-50 flex w-80 max-w-[85vw] flex-col overflow-y-auto bg-white lg:hidden">
        <div class="flex h-16 items-center justify-between px-5">
            <x-logo :tagline="false" class="[&_svg]:size-9" />
            <button type="button" @click="menuOpen = false" class="text-slate-400 hover:text-slate-600"><x-icon name="x" /></button>
        </div>
        <nav class="flex-1 space-y-6 px-5 pb-6 text-sm">
            <a href="{{ route('products.index') }}" class="block rounded-xl bg-slate-50 px-3 py-2.5 font-semibold text-slate-900">{{ __('All parts') }}</a>
            @if ($storeCategories->isNotEmpty())
                <div>
                    <p class="mb-2 text-xs font-semibold tracking-wide text-slate-400 uppercase">{{ __('Categories') }}</p>
                    @foreach ($storeCategories as $category)
                        <a href="{{ route('products.index', ['category' => $category->id]) }}" class="block rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-50">{{ $category->name }}</a>
                    @endforeach
                </div>
            @endif
            @if ($storeBrands->isNotEmpty())
                <div>
                    <p class="mb-2 text-xs font-semibold tracking-wide text-slate-400 uppercase">{{ __('Brands') }}</p>
                    @foreach ($storeBrands as $brand)
                        <a href="{{ route('products.index', ['brand' => $brand->id]) }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-50">
                            <x-brand-logo :brand="$brand" class="size-6 rounded ring-1 ring-slate-200" /> {{ $brand->name }}
                        </a>
                    @endforeach
                </div>
            @endif
            <x-language-switcher />
        </nav>
    </aside>

    <main class="flex-1">
        @if ($onAccountPage)
            {{-- Client account tabs --}}
            <div class="border-b border-slate-200 bg-white">
                <nav class="mx-auto flex max-w-7xl gap-1 overflow-x-auto px-4 sm:px-6 lg:px-8">
                    @foreach ($accountLinks as [$route, $pattern, $icon, $label])
                        <a href="{{ route($route) }}" @class(['flex shrink-0 items-center gap-2 border-b-2 px-3 py-3 text-sm font-medium transition', 'border-brand-600 text-brand-700' => request()->routeIs($pattern), 'border-transparent text-slate-500 hover:text-slate-900' => ! request()->routeIs($pattern)])>
                            <x-icon :name="$icon" class="size-4" /> {{ $label }}
                            @if ($route === 'cart.show')
                                <span x-cloak x-show="$store.cart.count > 0" x-text="$store.cart.count" class="rounded-full bg-amber-500 px-1.5 py-0.5 text-[11px] font-bold text-white tabular-nums"></span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </div>
        @endif

        @if ($bleed)
            <div class="mx-auto max-w-7xl px-4 empty:hidden sm:px-6 lg:px-8 [&>*]:mt-6"><x-flash /></div>
            {{ $slot }}
        @else
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <x-flash />
                {{ $slot }}
            </div>
        @endif
    </main>

    <footer class="mt-16 border-t border-slate-200 bg-white">
        <div class="border-b border-slate-100">
            <div class="mx-auto grid max-w-7xl gap-4 px-4 py-6 sm:grid-cols-3 sm:px-6 lg:px-8">
                @foreach ([
                    ['car', __('Parts matched to your car'), __('Choose your car and see what fits it.')],
                    ['clock', __('Live stock availability'), __('Quantities update from our warehouse.')],
                    ['document', __('Your account online'), __('Orders, invoices and payments in one place.')],
                ] as [$icon, $heading, $text])
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-slate-50 text-slate-600 ring-1 ring-slate-200"><x-icon :name="$icon" class="size-5" /></span>
                        <span>
                            <span class="block text-sm font-semibold text-slate-900">{{ $heading }}</span>
                            <span class="block text-xs text-slate-500">{{ $text }}</span>
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-slate-50">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
                <div class="lg:col-span-2">
                    <x-logo />
                    <p class="mt-3 max-w-sm text-sm leading-relaxed text-slate-500">{{ __('Genuine and aftermarket car parts, with live stock and parts matched to your car.') }}</p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('Shop') }}</p>
                    <ul class="mt-3 space-y-2 text-sm text-slate-500">
                        <li><a href="{{ route('products.index') }}" class="hover:text-slate-900">{{ __('All parts') }}</a></li>
                        @foreach ($storeCategories->take(5) as $category)
                            <li><a href="{{ route('products.index', ['category' => $category->id]) }}" class="hover:text-slate-900">{{ $category->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('My Account') }}</p>
                    <ul class="mt-3 space-y-2 text-sm text-slate-500">
                        @if ($user)
                            @foreach ($accountLinks as [$route, $pattern, $icon, $label])
                                <li><a href="{{ route($route) }}" class="hover:text-slate-900">{{ $label }}</a></li>
                            @endforeach
                        @else
                            <li><a href="{{ route('login') }}" class="hover:text-slate-900">{{ __('Login') }}</a></li>
                        @endif
                    </ul>
                </div>
            </div>
            <div class="border-t border-slate-200">
                <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-4 text-xs text-slate-500 sm:flex-row sm:px-6 lg:px-8">
                    <p>© {{ now()->year }} {{ __(config('app.name')) }} · {{ __('All rights reserved') }}</p>
                    <x-language-switcher />
                </div>
            </div>
        </div>
    </footer>
</div>
