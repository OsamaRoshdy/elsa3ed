<x-layouts.app :title="__('Dashboard')">
    <x-page-header :title="__('Welcome, :name', ['name' => auth()->user()->name])" :subtitle="__('Here is an overview of your account.')">
        <a href="{{ route('products.index') }}" class="btn-primary"><x-icon name="cube" class="size-4" /> {{ __('View Products') }}</a>
    </x-page-header>

    @if (! $client)
        @include('portal.not-linked')
    @else
        {{-- Hero balance --}}
        <div class="relative mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 p-6 text-white sm:p-8">
            <div class="absolute -top-16 -end-16 size-64 rounded-full bg-white/10 blur-3xl"></div>
            <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-white/70">{{ __('Outstanding Balance') }}</p>
                    <p class="mt-2 text-4xl font-bold tracking-tight sm:text-5xl"><x-money :amount="$stats['outstanding']" /></p>
                    <p class="mt-2 text-sm text-white/70">
                        {{ $stats['outstanding'] > 0 ? __('This is the amount you currently owe.') : __('You have no outstanding balance. Thank you!') }}
                    </p>
                </div>
                @if ($stats['outstanding'] > 0)
                    <a href="{{ route('invoices.index', ['status' => 'unpaid']) }}" class="btn bg-white text-brand-700 hover:bg-white/90">{{ __('View unpaid invoices') }}</a>
                @endif
            </div>
        </div>

        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-stat-card :label="__('Total Paid')" icon="banknotes" tone="emerald"><x-money :amount="$stats['paid']" /></x-stat-card>
            <x-stat-card :label="__('Total Invoices')" icon="document" tone="sky">{{ number_format($stats['invoices']) }}</x-stat-card>
            <x-stat-card :label="__('Last Payment')" icon="wallet" tone="amber" :hint="$stats['lastPayment']?->payment_date?->translatedFormat('d M Y')">
                @if ($stats['lastPayment'])
                    <x-money :amount="$stats['lastPayment']->amount" />
                @else
                    <span class="text-slate-400">—</span>
                @endif
            </x-stat-card>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-5">
            <div class="card overflow-hidden xl:col-span-3">
                <div class="flex items-center justify-between px-5 py-4">
                    <h2 class="font-semibold text-slate-900">{{ __('Recent Invoices') }}</h2>
                    <a href="{{ route('invoices.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">{{ __('View all') }}</a>
                </div>
                @if ($invoices->isEmpty())
                    <x-empty-state icon="document" :title="__('No invoices yet')" />
                @else
                    <x-invoice-table :invoices="$invoices" />
                @endif
            </div>

            <div class="card overflow-hidden xl:col-span-2">
                <div class="flex items-center justify-between px-5 py-4">
                    <h2 class="font-semibold text-slate-900">{{ __('Recent Payments') }}</h2>
                    <a href="{{ route('payments.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">{{ __('View all') }}</a>
                </div>
                @if ($payments->isEmpty())
                    <x-empty-state icon="banknotes" :title="__('No payments yet')" />
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($payments as $payment)
                            <li class="flex items-center justify-between gap-3 px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <span class="grid size-9 place-items-center rounded-xl bg-emerald-50 text-emerald-600"><x-icon name="banknotes" class="size-4" /></span>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900">{{ $payment->payment_date?->translatedFormat('d M Y') ?? '—' }}</p>
                                        <p class="text-xs text-slate-500">{{ $payment->payment_method ? __(\Illuminate\Support\Str::headline($payment->payment_method)) : '—' }}</p>
                                    </div>
                                </div>
                                <x-money :amount="$payment->amount" class="text-sm font-semibold text-emerald-700" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif
</x-layouts.app>
