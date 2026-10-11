<x-layouts.app :title="__('Webhooks')">
    <x-page-header :title="__('Webhooks')" :subtitle="__('Notifications received from Daftra.')" />

    <div class="card mb-6 p-6" x-data="{ copied: false }">
        <h2 class="font-semibold text-slate-900">{{ __('Webhook URL') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('In Daftra, open App Hub → Webhooks and create one webhook per event with this URL (HTTPS is required).') }}</p>
        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
            <input type="text" readonly value="{{ $webhookUrl }}" class="input font-mono text-xs" dir="ltr" x-ref="url">
            <button type="button" class="btn-secondary" @click="navigator.clipboard.writeText($refs.url.value); copied = true; setTimeout(() => copied = false, 2000)">
                <span x-show="!copied">{{ __('Copy') }}</span><span x-cloak x-show="copied">{{ __('Copied!') }}</span>
            </button>
        </div>
        @unless ($hasSecret)
            <p class="mt-3 flex items-start gap-2 rounded-xl bg-amber-50 p-3 text-sm text-amber-800">
                <x-icon name="warning" class="mt-0.5 size-4 text-amber-600" />
                {{ __('DAFTRA_WEBHOOK_SECRET is not set. Set it in .env so only requests with the secret token are accepted.') }}
            </p>
        @endunless
        <div class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
            <div class="rounded-xl bg-slate-50 p-3"><span class="font-semibold text-slate-900">{{ __('Product') }}:</span> <span class="text-slate-600">{{ __('created, updated, deleted') }}</span></div>
            <div class="rounded-xl bg-slate-50 p-3"><span class="font-semibold text-slate-900">{{ __('Client') }}:</span> <span class="text-slate-600">{{ __('created, updated, deleted') }}</span></div>
            <div class="rounded-xl bg-slate-50 p-3"><span class="font-semibold text-slate-900">{{ __('Invoice') }}:</span> <span class="text-slate-600">{{ __('created, deleted') }}</span></div>
        </div>
        <p class="mt-3 text-xs text-slate-500">{{ __('Daftra does not send webhooks for stock movements or payments, so those are kept accurate by the scheduled sync (every 15 minutes) and the manual “Sync with Daftra” button.') }}</p>
    </div>

    <div class="card overflow-hidden">
        @if ($logs->isEmpty())
            <x-empty-state icon="signal" :title="__('No webhooks received yet')" />
        @else
            @include('admin.partials.webhook-table', ['logs' => $logs])
            <div class="border-t border-slate-100 px-4 py-3">{{ $logs->links() }}</div>
        @endif
    </div>
</x-layouts.app>
