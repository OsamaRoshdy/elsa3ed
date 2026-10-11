<x-layouts.app :title="$tag->exists ? __('Edit tag') : __('New tag')">
    <a href="{{ route('admin.tags.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-900">
        <x-icon name="chevron" class="size-4 rotate-180 rtl:rotate-0" /> {{ __('Back to tags') }}
    </a>
    <x-page-header :title="$tag->exists ? __('Edit tag') : __('New tag')" />

    <form method="POST" action="{{ $tag->exists ? route('admin.tags.update', $tag) : route('admin.tags.store') }}" class="card max-w-2xl space-y-5 p-6"
        x-data="{ name: @js(old('name', $tag->name) ?? ''), color: @js(old('color', $tag->color)) }">
        @csrf
        @if ($tag->exists)
            @method('PUT')
        @endif

        <div>
            <label for="name" class="label">{{ __('Name') }}</label>
            <input id="name" name="name" type="text" x-model="name" required autofocus class="input" placeholder="{{ __('e.g. Original') }}">
            @error('name') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <span class="label">{{ __('Color') }}</span>
            <div class="flex flex-wrap gap-2">
                @foreach (\App\Models\Tag::COLORS as $value => $classes)
                    <label class="cursor-pointer">
                        <input type="radio" name="color" value="{{ $value }}" x-model="color" class="peer sr-only">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold ring-1 ring-inset transition peer-checked:ring-2 peer-checked:ring-slate-900 peer-focus-visible:outline-2 {{ $classes }}"
                            x-text="name || @js(__('Tag'))"></span>
                    </label>
                @endforeach
            </div>
            @error('color') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.tags.index') }}" class="btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="btn-primary">{{ $tag->exists ? __('Save changes') : __('Create tag') }}</button>
        </div>
    </form>
</x-layouts.app>
