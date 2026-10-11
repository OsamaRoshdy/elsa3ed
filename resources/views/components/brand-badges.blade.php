@props(['brands'])

@if ($brands->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'flex flex-wrap gap-1']) }}>
        @foreach ($brands as $brand)
            <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 py-0.5 ps-0.5 pe-1.5 text-xs font-medium text-slate-600">
                <x-brand-logo :brand="$brand" class="size-4 rounded" />
                {{ $brand->name }}
            </span>
        @endforeach
    </div>
@endif
