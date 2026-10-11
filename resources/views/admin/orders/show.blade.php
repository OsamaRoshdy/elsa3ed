<x-layouts.app :title="$order->number()">
    <a href="{{ route('admin.orders.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-900">
        <x-icon name="chevron" class="size-4 rotate-180 rtl:rotate-0" /> {{ __('Back to orders') }}
    </a>

    <x-page-header :title="__('Order :number', ['number' => $order->number()])" :subtitle="$order->submitted_at ? __('Sent :time', ['time' => $order->submitted_at->translatedFormat('d M Y, h:i A')]) : null">
        <x-order-status :status="$order->status" />
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="card p-6">
                <x-order-timeline :order="$order" />
            </div>

            <div class="card overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4">
                    <h2 class="font-semibold text-slate-900">{{ __('Items') }}</h2>
                    <span class="text-xs text-slate-500">{{ __('Use the SKU to find each product in Daftra.') }}</span>
                </div>
                <x-order-items :order="$order" show-stock />
            </div>

            @if ($order->notes)
                <div class="card p-6">
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('Client notes') }}</h2>
                    <p class="mt-2 text-sm whitespace-pre-line text-slate-600">{{ $order->notes }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            {{-- Client --}}
            <div class="card p-6">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Client') }}</h2>
                @if ($order->client)
                    <a href="{{ route('admin.clients.show', $order->client) }}" class="mt-2 block text-lg font-semibold text-brand-600 hover:text-brand-700">{{ $order->client->name }}</a>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Daftra ID') }}</dt><dd class="font-medium tabular-nums">{{ $order->client->daftra_id }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Phone') }}</dt><dd class="font-medium" dir="ltr">{{ $order->client->phone ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Outstanding Balance') }}</dt><dd class="font-semibold text-rose-600"><x-money :amount="$order->client->balance" /></dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">{{ __('Sent by') }}</dt><dd class="font-medium" dir="ltr">{{ $order->user?->email ?? '—' }}</dd></div>
                    </dl>
                @else
                    <p class="mt-2 text-sm text-slate-500">{{ __('Client no longer exists in Daftra.') }}</p>
                @endif
            </div>

            {{-- Actions --}}
            <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="card space-y-4 p-6" x-data="{ action: '' }">
                @csrf
                @method('PATCH')
                <h2 class="text-sm font-semibold text-slate-900">{{ __('Process order') }}</h2>

                @if ($order->status === 'submitted')
                    <p class="text-sm text-slate-500">{{ __('1. Mark as processing while you create the invoice in Daftra.') }}</p>
                    <button type="submit" name="action" value="processing" class="btn-secondary w-full">{{ __('Mark as processing') }}</button>
                @endif

                @if (in_array($order->status, ['submitted', 'processing']))
                    <div class="space-y-2 border-t border-slate-100 pt-4">
                        <p class="text-sm text-slate-500">{{ __('2. After creating the invoice in Daftra, sync and select it here:') }}</p>
                        <select name="invoice_id" data-searchable data-ajax-url="{{ route('admin.orders.invoices', $order) }}" data-placeholder="{{ __('Search invoice number...') }}">
                            <option value=""></option>
                            @if ($oldInvoice)
                                <option value="{{ $oldInvoice->id }}" selected>#{{ $oldInvoice->invoice_number }} — {{ $oldInvoice->invoice_date?->format('Y-m-d') }} — {{ number_format((float) $oldInvoice->total, 2) }}</option>
                            @endif
                        </select>
                        @error('invoice_id') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        @unless ($hasInvoices)
                            <p class="text-xs text-amber-600">{{ __('No invoices for this client yet. Create it in Daftra, then run a sync.') }}</p>
                        @endunless
                        <div class="flex gap-2">
                            <button type="submit" name="action" value="complete" class="btn-primary flex-1">{{ __('Link invoice & complete') }}</button>
                            <a href="{{ route('admin.sync.index') }}" class="btn-secondary" title="{{ __('Sync with Daftra') }}"><x-icon name="arrows" class="size-4" /></a>
                        </div>
                    </div>
                @endif

                @if ($order->invoice)
                    <div class="rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">
                        {{ __('Linked invoice') }}:
                        <a href="{{ route('admin.invoices.show', $order->invoice) }}" class="font-semibold underline">#{{ $order->invoice->invoice_number }}</a>
                        (<x-money :amount="$order->invoice->total" />)
                    </div>
                @endif

                <div class="border-t border-slate-100 pt-4">
                    <label for="admin_notes" class="label">{{ __('Message to client (optional)') }}</label>
                    <textarea id="admin_notes" name="admin_notes" rows="3" class="input" placeholder="{{ __('e.g. 2 items will be delivered next week') }}">{{ old('admin_notes', $order->admin_notes) }}</textarea>
                    <button type="submit" name="action" value="note" class="btn-secondary mt-2 w-full">{{ __('Save message') }}</button>
                </div>

                <div class="flex gap-2 border-t border-slate-100 pt-4">
                    @if (in_array($order->status, ['submitted', 'processing']))
                        <button type="submit" name="action" value="cancel" class="btn-secondary flex-1 text-rose-600" @click="if (! window.confirm(@js(__('Cancel this order?')))) $event.preventDefault()">{{ __('Cancel order') }}</button>
                    @else
                        <button type="submit" name="action" value="reopen" class="btn-secondary flex-1">{{ __('Reopen order') }}</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
