@props(['brand'])

<span {{ $attributes->merge(['class' => 'grid shrink-0 place-items-center overflow-hidden bg-white']) }}>
    @if ($brand->logo_url)
        <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" class="size-full object-contain p-0.5" loading="lazy">
    @else
        <span class="text-[0.7em] font-bold text-slate-400 uppercase">{{ mb_substr($brand->name, 0, 1) }}</span>
    @endif
</span>
