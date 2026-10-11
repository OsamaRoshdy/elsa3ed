@props(['title' => null, 'bleed' => false])

@php
    $user = auth()->user();
    $isRtl = app()->getLocale() === 'ar';

    // Admins get the sidebar portal; clients and guests get the storefront layout.
    $navigation = match (true) {
        $user?->isAdmin() => [
            ['admin.dashboard', 'admin.dashboard', 'home', __('Dashboard')],
            ['admin.products.index', 'admin.products.*', 'cube', __('Products')],
            ['admin.categories.index', 'admin.categories.*', 'squares', __('Categories')],
            ['admin.brands.index', 'admin.brands.*', 'tag', __('Brands')],
            ['admin.car-models.index', 'admin.car-models.*', 'car', __('Models')],
            ['admin.tags.index', 'admin.tags.*', 'badge', __('Tags')],
            ['admin.clients.index', 'admin.clients.*', 'users', __('Clients')],
            ['admin.invoices.index', 'admin.invoices.*', 'document', __('Invoices')],
            ['admin.orders.index', 'admin.orders.*', 'cart', __('Orders'), 'admin.orders'],
            ['admin.payments.index', 'admin.payments.*', 'banknotes', __('Payments')],
            ['admin.webhooks.index', 'admin.webhooks.*', 'signal', __('Webhooks')],
            ['admin.sync.index', 'admin.sync.*', 'arrows', __('Sync')],
            ['admin.users.index', 'admin.users.*', 'user', __('Users')],
            ['admin.settings.edit', 'admin.settings.*', 'cog', __('Settings')],
        ],
        default => [],
    };
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ __(config('app.name')) }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @fonts
    <script>
        window.portalLang = @js([
            'noResults' => __('No results found'),
            'searching' => __('Searching…'),
            'errorLoading' => __('The results could not be loaded.'),
            'loadingMore' => __('Loading more results…'),
            'genericError' => __('Something went wrong. Please try again.'),
        ]);
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-slate-800 antialiased">

@if ($user?->isAdmin())
    <div x-data="{ sidebarOpen: false }" class="min-h-full">
        {{-- Mobile overlay --}}
        <div x-cloak x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm lg:hidden"></div>

        {{-- Sidebar --}}
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '{{ $isRtl ? 'translate-x-full' : '-translate-x-full' }}'"
            class="fixed inset-y-0 start-0 z-50 flex w-72 flex-col border-e border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0 {{ $isRtl ? 'translate-x-full' : '-translate-x-full' }}"
        >
            <div class="flex h-16 items-center justify-between gap-2 px-6">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                    <x-logo />
                </a>
                <button type="button" @click="sidebarOpen = false" class="text-slate-400 hover:text-slate-600 lg:hidden"><x-icon name="x" /></button>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-4">
                @foreach ($navigation as $navItem)
                    @php([$route, $pattern, $icon, $label] = $navItem)
                    @php($badge = ($navCounts ?? [])[$navItem[4] ?? ''] ?? 0)
                    @php($active = request()->routeIs($pattern))
                    <a href="{{ route($route) }}" @class([
                        'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                        'bg-brand-50 text-brand-700' => $active,
                        'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! $active,
                    ])>
                        <x-icon :name="$icon" @class(['text-brand-600' => $active, 'text-slate-400 group-hover:text-slate-600' => ! $active]) />
                        <span class="flex-1">{{ $label }}</span>
                        @if (($navItem[4] ?? null) === 'cart')
                            <span x-cloak x-show="$store.cart.count > 0" x-text="$store.cart.count" class="rounded-full bg-amber-500 px-2 py-0.5 text-xs font-bold text-white tabular-nums"></span>
                        @elseif ($badge > 0)
                            <span class="rounded-full bg-amber-500 px-2 py-0.5 text-xs font-bold text-white tabular-nums">{{ $badge }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="border-t border-slate-100 p-4">
                <div class="flex items-center gap-3 rounded-xl px-2 py-2">
                    <span class="grid size-9 place-items-center rounded-full bg-slate-100 text-sm font-bold text-slate-600">{{ mb_substr($user->name, 0, 1) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ __('Administrator') }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-rose-50 hover:text-rose-700">
                        <x-icon name="logout" class="text-slate-400 rtl:-scale-x-100" />
                        {{ __('Logout') }}
                    </button>
                </form>
            </div>
        </aside>

        <div class="lg:ps-72">
            {{-- Top bar --}}
            <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-slate-200/70 bg-white/80 px-4 backdrop-blur sm:px-6 lg:px-8">
                {{-- The cart always sits on the physical right: last in LTR, first (after the menu button) in RTL. --}}
                <button type="button" @click="sidebarOpen = true" class="order-1 -m-2 p-2 text-slate-500 lg:hidden"><x-icon name="menu" class="size-6" /></button>
                <div class="order-3 flex-1"></div>
                <a href="{{ route('home') }}" class="order-3 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-900" target="_blank"><x-icon name="globe" class="size-4" /> {{ __('View store') }}</a>
                <x-language-switcher class="order-4" />
            </header>

            <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <x-flash />
                {{ $slot }}
            </main>
        </div>
    </div>
@else
    @include('components.layouts.partials.store', ['bleed' => $bleed])
@endif

<x-toasts />
<x-page-loader />
</body>
</html>
