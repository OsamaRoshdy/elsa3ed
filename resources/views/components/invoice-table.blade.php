@props(['invoices', 'route' => 'invoices.show', 'showClient' => false])

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>{{ __('Invoice #') }}</th>
                @if ($showClient)
                    <th>{{ __('Client') }}</th>
                @endif
                <th>{{ __('Date') }}</th>
                <th>{{ __('Total') }}</th>
                <th>{{ __('Paid') }}</th>
                <th>{{ __('Remaining') }}</th>
                <th>{{ __('Status') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoices as $invoice)
                <tr>
                    <td class="font-semibold text-slate-900">#{{ $invoice->invoice_number ?? $invoice->daftra_id }}</td>
                    @if ($showClient)
                        <td>
                            @if ($invoice->client)
                                <a href="{{ route('admin.clients.show', $invoice->client) }}" class="hover:text-brand-600">{{ $invoice->client->name }}</a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                    @endif
                    <td>{{ $invoice->invoice_date?->translatedFormat('d M Y') ?? '—' }}</td>
                    <td><x-money :amount="$invoice->total" :currency="$invoice->currency" /></td>
                    <td class="text-emerald-700"><x-money :amount="$invoice->paid" :currency="$invoice->currency" /></td>
                    <td @class(['font-semibold', 'text-rose-600' => $invoice->remaining > 0, 'text-slate-400' => $invoice->remaining <= 0])>
                        <x-money :amount="$invoice->remaining" :currency="$invoice->currency" />
                    </td>
                    <td><x-invoice-status :status="$invoice->status" /></td>
                    <td class="text-end">
                        <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="me-3 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-900" title="{{ __('View PDF') }}">
                            <x-icon name="document" class="size-4" /> PDF
                        </a>
                        <a href="{{ route($route, $invoice) }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:text-brand-700">
                            {{ __('View') }} <x-icon name="chevron" class="size-4 rtl:-scale-x-100" />
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
