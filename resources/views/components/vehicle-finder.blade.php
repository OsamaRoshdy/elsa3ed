@props([
    // hero: big card on the home page · bar: horizontal row on the catalogue · stacked: header dropdown / product page
    'variant' => 'hero',
    'stay' => false,
    'brand' => null,
    'model' => null,
    'year' => null,
])

@php
    $garage = app(\App\Support\Garage::class)->current();
    $finderBrands = \App\Models\Brand::forVehicleFinder()
        ->map(fn ($brand) => ['id' => $brand->id, 'name' => $brand->name, 'models' => $brand->carModels->map(fn ($carModel) => ['id' => $carModel->id, 'name' => $carModel->name])->values()])
        ->values();
    $selected = [
        'brand' => (string) ($brand ?? $garage['brand']->id ?? ''),
        'model' => (string) ($model ?? $garage['model']->id ?? ''),
        'year' => (string) ($year ?? $garage['year'] ?? ''),
    ];
    $years = range(\App\Models\Fitment::lastYear(), \App\Models\Fitment::FIRST_YEAR);
@endphp

<form
    method="POST"
    action="{{ route('vehicle.store') }}"
    x-data="{
        brands: @js($finderBrands),
        brand: @js($selected['brand']),
        model: @js($selected['model']),
        year: @js($selected['year']),
        get models() { return this.brands.find(b => String(b.id) === this.brand)?.models ?? [] },
    }"
    {{ $attributes->class([
        'grid gap-3',
        'sm:grid-cols-3' => $variant === 'hero',
        'sm:grid-cols-[repeat(3,minmax(0,1fr))_auto]' => $variant === 'bar',
    ]) }}
>
    @csrf
    @if ($stay)
        <input type="hidden" name="stay" value="1">
    @endif

    @foreach ([
        ['brand', 1, __('Brand'), null],
        ['model', 2, __('Model'), __('Model')],
        ['year', 3, __('Year (optional)'), __('Year')],
    ] as [$field, $step, $placeholder, $disabledPlaceholder])
        <label class="relative flex items-center rounded-xl border border-slate-200 bg-white transition focus-within:border-brand-500 focus-within:ring-4 focus-within:ring-brand-100"
            :class="{{ $field === 'brand' ? 'false' : ($field === 'model' ? '! brand' : '! model') }} && 'bg-slate-50 opacity-70'">
            <span class="ms-3 grid size-5 shrink-0 place-items-center rounded-full bg-slate-100 text-[11px] font-bold text-slate-500">{{ $step }}</span>
            <span class="sr-only">{{ $placeholder }}</span>
            <select
                name="{{ $field }}"
                x-model="{{ $field }}"
                class="w-full min-w-0 cursor-pointer appearance-none border-0 bg-transparent py-3 ps-2.5 pe-9 text-sm text-slate-900 focus:ring-0 focus:outline-none disabled:cursor-not-allowed"
                @if ($field === 'brand') required @change="model = ''; year = ''" @endif
                @if ($field === 'model') :disabled="! brand" @change="year = ''" @endif
                @if ($field === 'year') :disabled="! model" @endif
            >
                @if ($field === 'brand')
                    <option value="">{{ $placeholder }}</option>
                    <template x-for="item in brands" :key="item.id">
                        <option :value="String(item.id)" x-text="item.name" :selected="String(item.id) === brand"></option>
                    </template>
                @elseif ($field === 'model')
                    <option value="" x-text="brand ? @js(__('All models')) : @js($disabledPlaceholder)"></option>
                    <template x-for="item in models" :key="item.id">
                        <option :value="String(item.id)" x-text="item.name" :selected="String(item.id) === model"></option>
                    </template>
                @else
                    <option value="" x-text="model ? @js(__('All years')) : @js($disabledPlaceholder)"></option>
                    @foreach ($years as $yearOption)
                        <option value="{{ $yearOption }}" :selected="year === '{{ $yearOption }}'">{{ $yearOption }}</option>
                    @endforeach
                @endif
            </select>
            <x-icon name="chevron" class="pointer-events-none absolute end-3 size-4 rotate-90 text-slate-400" />
        </label>
    @endforeach

    <button type="submit" @class([
        'btn-primary',
        'sm:col-span-3 py-3 text-base' => $variant === 'hero',
        'px-6' => $variant === 'bar',
    ]) :disabled="! brand">
        @if ($stay)
            {{ __('Check fitment') }}
        @else
            {{ __('Show parts') }}
        @endif
    </button>
</form>
