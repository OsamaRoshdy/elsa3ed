<x-layouts.app :title="$order->number()">
    <a href="{{ route('orders.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-900">
        <x-icon name="chevron" class="size-4 rotate-180 rtl:rotate-0" /> {{ __('Back to orders') }}
    </a>

    <x-page-header :title="__('Order :number', ['number' => $order->number()])">
        <x-order-status :status="$order->status" />
        @if ($order->canBeCancelledByClient())
            <form method="POST" action="{{ route('orders.cancel', $order) }}" x-data @submit="if (! window.confirm(@js(__('Cancel this order?')))) $event.preventDefault()">
                @csrf
                <button type="submit" class="btn-secondary text-rose-600">{{ __('Cancel order') }}</button>
            </form>
        @endif
    </x-page-header>

    <div class="card mb-6 p-6">
        <x-order-timeline :order="$order" />

        @if ($order->invoice)
            <div class="mt-6 flex flex-col gap-3 rounded-2xl bg-emerald-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-emerald-800">{{ __('Your invoice for this order is ready.') }}</p>
                <a href="{{ route('invoices.show', $order->invoice) }}" class="btn-primary">{{ __('View invoice #:number', ['number' => $order->invoice->invoice_number]) }}</a>
            </div>
        @endif

        @if ($order->admin_notes)
            <div class="mt-6 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900">
                <p class="font-semibold">{{ __('Message from us') }}</p>
                <p class="mt-1 whitespace-pre-line">{{ $order->admin_notes }}</p>
            </div>
        @endif
    </div>

    <div class="card overflow-hidden">
        <x-order-items :order="$order" />
    </div>

    @if ($order->notes)
        <div class="card mt-6 p-6">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Your notes') }}</h2>
            <p class="mt-2 text-sm whitespace-pre-line text-slate-600">{{ $order->notes }}</p>
        </div>
    @endif
</x-layouts.app>
