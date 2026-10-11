@props(['status'])

@php
    [$class, $label] = [
        'draft' => ['bg-slate-50 text-slate-600 ring-slate-500/20', __('Draft')],
        'submitted' => ['bg-sky-50 text-sky-700 ring-sky-600/20', __('Submitted')],
        'processing' => ['bg-amber-50 text-amber-700 ring-amber-600/20', __('Processing')],
        'completed' => ['bg-emerald-50 text-emerald-700 ring-emerald-600/20', __('Completed')],
        'cancelled' => ['bg-rose-50 text-rose-700 ring-rose-600/20', __('Cancelled')],
    ][$status] ?? ['bg-slate-50 text-slate-600 ring-slate-500/20', $status];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {$class}"]) }}>{{ $label }}</span>
