@props(['placeholder' => __('Search...'), 'value' => request('q')])

<form method="GET" {{ $attributes->merge(['class' => 'flex flex-col gap-3 sm:flex-row sm:items-center']) }}>
    <div class="relative flex-1">
        <x-icon name="search" class="pointer-events-none absolute inset-y-0 start-3.5 my-auto size-4 text-slate-400" />
        <input type="search" name="q" value="{{ $value }}" placeholder="{{ $placeholder }}" class="input ps-10">
    </div>
    {{ $slot }}
    <button type="submit" class="btn-primary">{{ __('Search') }}</button>
</form>
