<x-layouts.app :title="$client->name">
    <a href="{{ route('admin.clients.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-900">
        <x-icon name="chevron" class="size-4 rotate-180 rtl:rotate-0" /> {{ __('Back to clients') }}
    </a>

    <div class="card mb-6 p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <span class="grid size-14 place-items-center rounded-2xl bg-brand-50 text-xl font-bold text-brand-700">{{ mb_substr($client->name, 0, 1) }}</span>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">{{ $client->name }}</h1>
                    <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500">
                        <span dir="ltr">{{ $client->email ?? '—' }}</span>
                        <span dir="ltr">{{ $client->phone ?? '—' }}</span>
                        <span>{{ __('Daftra ID') }}: {{ $client->daftra_id }}</span>
                    </div>
                </div>
            </div>
            @if ($client->users->isEmpty())
                <a href="{{ route('admin.users.create', ['client' => $client->daftra_id]) }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('Create portal login') }}</a>
            @else
                <div class="text-sm text-slate-500">
                    {{ __('Portal login') }}:
                    @foreach ($client->users as $user)
                        <a href="{{ route('admin.users.edit', $user) }}" class="font-medium text-brand-600" dir="ltr">{{ $user->email }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card :label="__('Total Invoices')" icon="document" tone="sky">{{ number_format($stats['invoices']) }}</x-stat-card>
        <x-stat-card :label="__('Total Invoiced')" icon="document"><x-money :amount="$stats['invoiced']" /></x-stat-card>
        <x-stat-card :label="__('Total Paid')" icon="banknotes" tone="emerald"><x-money :amount="$stats['paid']" /></x-stat-card>
        <x-stat-card :label="__('Outstanding Balance')" icon="wallet" :tone="$stats['outstanding'] > 0 ? 'rose' : 'emerald'"><x-money :amount="$stats['outstanding']" /></x-stat-card>
    </div>

    <div class="card mb-6 overflow-hidden">
        <h2 class="px-5 py-4 font-semibold text-slate-900">{{ __('Invoices') }}</h2>
        @if ($invoices->isEmpty())
            <x-empty-state icon="document" :title="__('No invoices yet')" />
        @else
            <x-invoice-table :invoices="$invoices" route="admin.invoices.show" />
            <div class="border-t border-slate-100 px-4 py-3">{{ $invoices->links() }}</div>
        @endif
    </div>

    <div class="card overflow-hidden">
        <h2 class="px-5 py-4 font-semibold text-slate-900">{{ __('Payments') }}</h2>
        @if ($payments->isEmpty())
            <x-empty-state icon="banknotes" :title="__('No payments yet')" />
        @else
            <x-payment-table :payments="$payments" invoice-route="admin.invoices.show" />
            <div class="border-t border-slate-100 px-4 py-3">{{ $payments->links() }}</div>
        @endif
    </div>
</x-layouts.app>
