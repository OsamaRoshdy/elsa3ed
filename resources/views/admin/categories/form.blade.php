<x-layouts.app :title="$category->exists ? __('Edit category') : __('New category')">
    <a href="{{ route('admin.categories.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-900">
        <x-icon name="chevron" class="size-4 rotate-180 rtl:rotate-0" /> {{ __('Back to categories') }}
    </a>
    <x-page-header :title="$category->exists ? __('Edit category') : __('New category')" />

    <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" enctype="multipart/form-data" class="card max-w-2xl space-y-5 p-6">
        @csrf
        @if ($category->exists)
            @method('PUT')
        @endif

        <div>
            <label for="name" class="label">{{ __('Name') }}</label>
            <input id="name" name="name" type="text" value="{{ old('name', $category->name) }}" required autofocus class="input">
            @error('name') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div x-data="{ preview: @js($category->image_url), removed: false }">
            <label for="image" class="label">{{ __('Image') }}</label>
            <div class="flex items-center gap-4">
                <span class="grid size-20 shrink-0 place-items-center overflow-hidden rounded-xl bg-white ring-1 ring-slate-200">
                    <img x-show="preview && ! removed" :src="preview" alt="" class="size-full object-contain p-1" x-cloak>
                    <x-icon name="photo" class="size-8 text-slate-300" x-show="! preview || removed" />
                </span>
                <div class="min-w-0 flex-1 space-y-2">
                    <input id="image" name="image" type="file" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-slate-600 file:me-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200"
                        @change="const file = $event.target.files[0]; if (file) { preview = URL.createObjectURL(file); removed = false; }">
                    <p class="text-xs text-slate-500">{{ __('PNG, JPG or WEBP, up to 2 MB. A square photo of the part on a white or transparent background looks best.') }}</p>
                    @if ($category->image_path)
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="remove_image" value="1" x-model="removed" class="size-4 rounded border-slate-300 text-rose-600">
                            {{ __('Remove image') }}
                        </label>
                    @endif
                </div>
            </div>
            @error('image') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.categories.index') }}" class="btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="btn-primary">{{ $category->exists ? __('Save changes') : __('Create category') }}</button>
        </div>
    </form>
</x-layouts.app>
