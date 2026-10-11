@props(['payments', 'showClient' => false, 'invoiceRoute' => 'invoices.show'])

<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>{{ __('Date') }}</th>
                @if ($showClient)
                    <th>{{ __('Client') }}</th>
                @endif
                <th>{{ __('Amount') }}</th>
                <th>{{ __('Method') }}</th>
                <th>{{ __('Invoice') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payments as $payment)
                <tr>
                    <td>{{ $payment->payment_date?->translatedFormat('d M Y') ?? '—' }}</td>
                    @if ($showClient)
                        <td>
                            @if ($payment->client)
                                <a href="{{ route('admin.clients.show', $payment->client) }}" class="hover:text-brand-600">{{ $payment->client->name }}</a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                    @endif
                    <td class="font-semibold text-emerald-700"><x-money :amount="$payment->amount" /></td>
                    <td>
                        <span class="inline-flex rounded-lg bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">{{ $payment->payment_method ? __(\Illuminate\Support\Str::headline($payment->payment_method)) : '—' }}</span>
                    </td>
                    <td>
                        @if ($payment->invoice)
                            <a href="{{ route($invoiceRoute, $payment->invoice) }}" class="font-medium text-brand-600 hover:text-brand-700">#{{ $payment->invoice->invoice_number }}</a>
                        @else
                            <span class="text-slate-400">{{ __('On account') }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
