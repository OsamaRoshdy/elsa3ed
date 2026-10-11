<x-layouts.app :title="__('My Account')">
    <x-page-header :title="__('My Account')" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card p-6">
            <div class="flex items-center gap-4">
                <span class="grid size-14 place-items-center rounded-2xl bg-brand-50 text-xl font-bold text-brand-700">{{ mb_substr($user->name, 0, 1) }}</span>
                <div class="min-w-0">
                    <p class="truncate text-lg font-semibold text-slate-900">{{ $client?->name ?? $user->name }}</p>
                    <p class="truncate text-sm text-slate-500" dir="ltr">{{ $user->email }}</p>
                </div>
            </div>
            <dl class="mt-6 space-y-4 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-500">{{ __('Portal login') }}</dt>
                    <dd class="font-medium text-slate-900" dir="ltr">{{ $user->email }}</dd>
                </div>
                @if ($client)
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Email') }}</dt>
                        <dd class="font-medium text-slate-900" dir="ltr">{{ $client->email ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Phone') }}</dt>
                        <dd class="font-medium text-slate-900" dir="ltr">{{ $client->phone ?? '—' }}</dd>
                    </div>
                    @if ($client->credit_limit > 0)
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-500">{{ __('Credit limit') }}</dt>
                            <dd class="font-medium text-slate-900"><x-money :amount="$client->credit_limit" /></dd>
                        </div>
                    @endif
                @endif
            </dl>
        </div>

        <div class="lg:col-span-2">
            @if (! $client)
                @include('portal.not-linked')
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-stat-card :label="__('Outstanding Balance')" icon="wallet" :tone="$stats['outstanding'] > 0 ? 'rose' : 'emerald'"><x-money :amount="$stats['outstanding']" /></x-stat-card>
                    <x-stat-card :label="__('Total Paid')" icon="banknotes" tone="emerald"><x-money :amount="$stats['paid']" /></x-stat-card>
                    <x-stat-card :label="__('Total Invoiced')" icon="document" tone="sky"><x-money :amount="$stats['invoiced']" /></x-stat-card>
                    <x-stat-card :label="__('Total Invoices')" icon="document">{{ number_format($stats['invoices']) }}</x-stat-card>
                </div>
                <p class="mt-4 flex items-center gap-1.5 text-xs text-slate-400">
                    <x-icon name="clock" class="size-3.5" />
                    {{ __('Last synchronized: :time', ['time' => $client->last_synced_at?->diffForHumans() ?? '—']) }}
                </p>
            @endif
        </div>
    </div>
</x-layouts.app>
