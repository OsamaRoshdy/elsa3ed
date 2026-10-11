<x-layouts.app :title="__('Dashboard')">
    <x-page-header :title="__('Dashboard')" :subtitle="__('Overview of your Daftra data.')">
        <x-sync-button />
    </x-page-header>

    @if ($newOrders > 0)
        <a href="{{ route('admin.orders.index', ['status' => 'submitted']) }}" class="mb-6 flex items-center justify-between gap-4 rounded-2xl bg-amber-50 p-4 ring-1 ring-amber-200 transition hover:bg-amber-100">
            <span class="flex items-center gap-3 text-sm font-medium text-amber-900">
                <span class="grid size-9 place-items-center rounded-xl bg-amber-500 text-white"><x-icon name="cart" class="size-5" /></span>
                {{ trans_choice('You have :count new order waiting.|You have :count new orders waiting.', $newOrders, ['count' => $newOrders]) }}
            </span>
            <span class="text-sm font-semibold text-amber-700">{{ __('Review') }} <x-icon name="chevron" class="inline size-4 rtl:-scale-x-100" /></span>
        </a>
    @endif

    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card :label="__('Total Products')" icon="cube">{{ number_format($stats['products']) }}</x-stat-card>
        <x-stat-card :label="__('Total Clients')" icon="users" tone="sky">{{ number_format($stats['clients']) }}</x-stat-card>
        <x-stat-card :label="__('Total Outstanding')" icon="wallet" tone="rose"><x-money :amount="$stats['outstanding']" /></x-stat-card>
        <a href="{{ route('admin.products.index', ['low' => 1]) }}" class="block">
            <x-stat-card :label="__('Low Stock Products')" icon="warning" tone="amber" class="h-full transition hover:shadow-md">{{ number_format($stats['lowStock']) }}</x-stat-card>
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- Recent synchronization --}}
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-slate-900">{{ __('Recent synchronization') }}</h2>
                <a href="{{ route('admin.sync.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">{{ __('History') }}</a>
            </div>
            @if ($lastRun)
                <dl class="mt-5 space-y-3.5 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Last Daftra Sync') }}</dt>
                        <dd class="font-medium text-slate-900">{{ $lastRun->started_at?->translatedFormat('d M Y, h:i A') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Status') }}</dt>
                        <dd><x-sync-status :run="$lastRun" /></dd>
                    </div>
                    @foreach (['products_count' => __('Products synced'), 'clients_count' => __('Clients synced'), 'invoices_count' => __('Invoices synced'), 'payments_count' => __('Payments synced')] as $field => $label)
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">{{ $label }}</dt>
                            <dd class="font-semibold text-slate-900 tabular-nums">{{ number_format($lastRun->{$field}) }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($lastRun->error)
                    <p class="mt-4 rounded-xl bg-rose-50 p-3 text-xs text-rose-700">{{ $lastRun->error }}</p>
                @endif
            @else
                <x-empty-state icon="arrows" :title="__('No synchronization yet')" :description="__('Click “Sync with Daftra” to import your data.')" class="py-8" />
            @endif
        </div>

        {{-- Low stock --}}
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5">
                <h2 class="font-semibold text-slate-900">{{ __('Low Stock Products') }}</h2>
                <a href="{{ route('admin.products.index', ['low' => 1]) }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">{{ __('View all') }}</a>
            </div>
            @if ($lowStockProducts->isEmpty())
                <x-empty-state icon="check" :title="__('Stock levels look good')" class="py-8" />
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($lowStockProducts as $product)
                        <li class="flex items-center justify-between gap-3 px-6 py-3">
                            <a href="{{ route('products.show', $product) }}" class="min-w-0 truncate text-sm font-medium text-slate-900 hover:text-brand-600">{{ $product->name }}</a>
                            <span class="shrink-0 rounded-lg bg-amber-50 px-2 py-1 text-xs font-bold text-amber-700 tabular-nums">{{ (float) $product->stock_quantity + 0 }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Top balances --}}
        <div class="card overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5">
                <h2 class="font-semibold text-slate-900">{{ __('Highest balances') }}</h2>
                <a href="{{ route('admin.clients.index', ['owing' => 1]) }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">{{ __('View all') }}</a>
            </div>
            @if ($topBalances->isEmpty())
                <x-empty-state icon="check" :title="__('No outstanding balances')" class="py-8" />
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($topBalances as $client)
                        <li class="flex items-center justify-between gap-3 px-6 py-3">
                            <a href="{{ route('admin.clients.show', $client) }}" class="min-w-0 truncate text-sm font-medium text-slate-900 hover:text-brand-600">{{ $client->name }}</a>
                            <x-money :amount="$client->balance" class="shrink-0 text-sm font-semibold text-rose-600" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="card mt-6 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-5">
            <h2 class="font-semibold text-slate-900">{{ __('Recent webhooks') }}</h2>
            <a href="{{ route('admin.webhooks.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">{{ __('View all') }}</a>
        </div>
        @if ($recentWebhooks->isEmpty())
            <x-empty-state icon="signal" :title="__('No webhooks received yet')" class="py-8" />
        @else
            @include('admin.partials.webhook-table', ['logs' => $recentWebhooks])
        @endif
    </div>
</x-layouts.app>
