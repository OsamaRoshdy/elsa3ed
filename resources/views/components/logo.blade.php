@props(['inverted' => false, 'tagline' => true])

{{-- Full brand lockup: mark + "ال سعد" wordmark + tagline. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <x-logo-mark class="size-10 shrink-0 drop-shadow-sm" />
    <span class="flex flex-col leading-none">
        <span @class(['font-arabic text-xl font-extrabold tracking-tight', 'text-slate-900' => ! $inverted, 'text-white' => $inverted])>ال سعد</span>
        @if ($tagline)
            <span @class(['mt-1 text-[11px] font-semibold', 'text-amber-600' => ! $inverted, 'text-amber-300' => $inverted])>{{ __('Auto Parts') }}</span>
        @endif
    </span>
</span>
