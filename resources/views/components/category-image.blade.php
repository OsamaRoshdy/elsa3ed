@props(['category'])

<span {{ $attributes->merge(['class' => 'grid shrink-0 place-items-center overflow-hidden bg-slate-50']) }}>
    @if ($category->image_url)
        <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="size-full object-contain" loading="lazy">
    @else
        <x-icon name="squares" class="size-1/2 text-slate-300" />
    @endif
</span>
