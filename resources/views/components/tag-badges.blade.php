@props(['tags'])

@if ($tags->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'flex flex-wrap gap-1']) }}>
        @foreach ($tags as $tag)
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $tag->colorClasses() }}">{{ $tag->name }}</span>
        @endforeach
    </div>
@endif
