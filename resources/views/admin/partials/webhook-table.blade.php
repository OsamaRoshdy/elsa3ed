<div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>{{ __('Webhook') }}</th>
                <th>{{ __('Event') }}</th>
                <th>{{ __('Daftra ID') }}</th>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                <tr x-data="{ open: false }">
                    <td class="font-medium text-slate-900">#{{ $log->id }}</td>
                    <td><code class="rounded-md bg-slate-100 px-1.5 py-0.5 text-xs">{{ $log->event ?? '—' }}</code></td>
                    <td class="tabular-nums">{{ $log->daftra_id ?? '—' }}</td>
                    <td>{{ $log->created_at->translatedFormat('d M Y, h:i A') }}</td>
                    <td class="whitespace-normal">
                        @if ($log->processed)
                            <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20 ring-inset">{{ __('Processed') }}</span>
                        @elseif ($log->error)
                            <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-600/20 ring-inset">{{ __('Failed') }}</span>
                            <p class="mt-1 max-w-sm text-xs text-rose-600">{{ $log->error }}</p>
                        @else
                            <span class="inline-flex rounded-full bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-600/20 ring-inset">{{ __('Pending') }}</span>
                        @endif
                        <button type="button" @click="open = !open" class="ms-2 text-xs font-medium text-brand-600 hover:text-brand-700">{{ __('Payload') }}</button>
                        <pre x-cloak x-show="open" class="mt-2 max-h-64 max-w-xl overflow-auto rounded-xl bg-slate-900 p-3 text-start text-xs text-slate-100" dir="ltr">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
