@props(['label', 'icon' => 'cube', 'tone' => 'brand', 'hint' => null])

@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'rose' => 'bg-rose-50 text-rose-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'sky' => 'bg-sky-50 text-sky-600',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
            <div class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ $slot }}</div>
            @if ($hint)
                <p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>
            @endif
        </div>
        <span class="grid size-11 shrink-0 place-items-center rounded-xl {{ $tones[$tone] ?? $tones['brand'] }}">
            <x-icon :name="$icon" class="size-6" />
        </span>
    </div>
</div>
