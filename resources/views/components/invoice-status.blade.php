@props(['status'])

@php
    [$class, $label] = [
        'paid' => ['bg-emerald-50 text-emerald-700 ring-emerald-600/20', __('Paid')],
        'partial' => ['bg-amber-50 text-amber-700 ring-amber-600/20', __('Partially paid')],
        'unpaid' => ['bg-rose-50 text-rose-700 ring-rose-600/20', __('Unpaid')],
        'overpaid' => ['bg-sky-50 text-sky-700 ring-sky-600/20', __('Overpaid')],
    ][$status] ?? ['bg-slate-50 text-slate-700 ring-slate-600/20', $status];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {$class}"]) }}>{{ $label }}</span>
