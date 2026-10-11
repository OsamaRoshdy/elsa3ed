<x-layouts.app :title="__('Clients')">
    <x-page-header :title="__('Clients')" :subtitle="__(':count clients synced from Daftra', ['count' => number_format($clients->total())])" />

    <div class="card mb-6 p-4">
        <x-search-form :placeholder="__('Search by name, email or phone...')">
            <label class="flex items-center gap-2 text-sm whitespace-nowrap text-slate-600">
                <input type="checkbox" name="owing" value="1" @checked($filters['owing'] ?? false) onchange="this.form.submit()" class="size-4 rounded border-slate-300 text-brand-600">
                {{ __('With balance only') }}
            </label>
        </x-search-form>
    </div>

    <div class="card overflow-hidden">
        @if ($clients->isEmpty())
            <x-empty-state icon="users" :title="__('No clients found')" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Client') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('Invoices') }}</th>
                            <th>{{ __('Outstanding Balance') }}</th>
                            <th>{{ __('Portal access') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clients as $client)
                            <tr>
                                <td>
                                    <p class="font-medium text-slate-900">{{ $client->name }}</p>
                                    <p class="text-xs text-slate-500" dir="ltr">{{ $client->email ?? '' }}</p>
                                </td>
                                <td dir="ltr" class="text-start">{{ $client->phone ?? '—' }}</td>
                                <td class="tabular-nums">{{ number_format($client->invoices_count) }}</td>
                                <td @class(['font-semibold', 'text-rose-600' => $client->balance > 0, 'text-slate-400' => $client->balance <= 0])><x-money :amount="$client->balance" /></td>
                                <td>
                                    @if ($client->users_exists)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ __('Active') }}</span>
                                    @else
                                        <a href="{{ route('admin.users.create', ['client' => $client->daftra_id]) }}" class="text-xs font-medium text-brand-600 hover:text-brand-700">+ {{ __('Create login') }}</a>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.clients.show', $client) }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:text-brand-700">{{ __('View') }} <x-icon name="chevron" class="size-4 rtl:-scale-x-100" /></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">{{ $clients->links() }}</div>
        @endif
    </div>
</x-layouts.app>
