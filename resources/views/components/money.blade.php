@props(['amount' => 0, 'currency' => null])

<span {{ $attributes->merge(['class' => 'tabular-nums']) }}>{{ number_format((float) $amount, 2) }} <span class="text-[0.8em] font-medium opacity-60">{{ $currency ?: config('services.daftra.currency') }}</span></span>
