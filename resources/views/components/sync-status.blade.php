@props(['run'])

@php
    $styles = [
        'success' => ['bg-emerald-50 text-emerald-700 ring-emerald-600/20', __('Successful')],
        'failed' => ['bg-rose-50 text-rose-700 ring-rose-600/20', __('Failed')],
        'running' => ['bg-sky-50 text-sky-700 ring-sky-600/20', __('Running')],
    ][$run->status] ?? ['bg-slate-50 text-slate-700 ring-slate-600/20', $run->status];
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $styles[0] }}">{{ $styles[1] }}</span>
