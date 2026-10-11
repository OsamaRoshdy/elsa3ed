<x-layouts.app :title="__('Sync')">
    <x-page-header :title="__('Synchronization')" :subtitle="__('Daftra is the source of truth. Synchronization imports products, stock, clients, invoices and payments.')">
        <form method="POST" action="{{ route('admin.sync.test') }}">
            @csrf
            <button type="submit" class="btn-secondary" @disabled(! $isConfigured)><x-icon name="signal" class="size-4" /> {{ __('Test connection') }}</button>
        </form>
        @if ($isConfigured)
            <x-sync-button />
        @endif
    </x-page-header>

    <div class="card mb-6 p-6">
        <div class="flex items-start gap-4">
            <span @class(['grid size-11 shrink-0 place-items-center rounded-xl', 'bg-emerald-50 text-emerald-600' => $isConfigured, 'bg-amber-50 text-amber-600' => ! $isConfigured])>
                <x-icon :name="$isConfigured ? 'check' : 'warning'" class="size-6" />
            </span>
            <div class="min-w-0">
                <h2 class="font-semibold text-slate-900">{{ $isConfigured ? __('Daftra API configured') : __('Daftra API not configured') }}</h2>
                @if ($isConfigured)
                    <p class="mt-1 truncate text-sm text-slate-500" dir="ltr">{{ $baseUrl }}</p>
                @else
                    <p class="mt-1 text-sm text-slate-500">{{ __('Add your credentials to the .env file on the server:') }}</p>
                    <pre class="mt-3 overflow-x-auto rounded-xl bg-slate-900 p-4 text-xs text-slate-100" dir="ltr">DAFTRA_API_BASE_URL=https://your-subdomain.daftra.com/api2
DAFTRA_API_TOKEN=your-api-key   # Daftra → Settings → API → API Key
DAFTRA_WEBHOOK_SECRET=a-long-random-string</pre>
                @endif
            </div>
        </div>
    </div>

    <div class="card overflow-hidden">
        <h2 class="px-5 py-4 font-semibold text-slate-900">{{ __('Sync history') }}</h2>
        @if ($runs->isEmpty())
            <x-empty-state icon="arrows" :title="__('No synchronization yet')" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Started') }}</th>
                            <th>{{ __('Trigger') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Products') }}</th>
                            <th>{{ __('Clients') }}</th>
                            <th>{{ __('Invoices') }}</th>
                            <th>{{ __('Payments') }}</th>
                            <th>{{ __('Duration') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($runs as $run)
                            <tr>
                                <td>{{ $run->started_at?->translatedFormat('d M Y, h:i A') }}</td>
                                <td>{{ $run->triggered_by === 'manual' ? __('Manual') : __('Scheduled') }}</td>
                                <td class="whitespace-normal">
                                    <x-sync-status :run="$run" />
                                    @if ($run->error)
                                        <p class="mt-1 max-w-xs text-xs text-rose-600">{{ $run->error }}</p>
                                    @endif
                                </td>
                                <td class="tabular-nums">{{ number_format($run->products_count) }}</td>
                                <td class="tabular-nums">{{ number_format($run->clients_count) }}</td>
                                <td class="tabular-nums">{{ number_format($run->invoices_count) }}</td>
                                <td class="tabular-nums">{{ number_format($run->payments_count) }}</td>
                                <td class="text-slate-500">{{ $run->finished_at ? $run->started_at->diffForHumans($run->finished_at, true) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">{{ $runs->links() }}</div>
        @endif
    </div>
</x-layouts.app>
