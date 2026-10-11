<x-layouts.app :title="$carModel->exists ? __('Edit model') : __('New model')">
    <a href="{{ route('admin.car-models.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-900">
        <x-icon name="chevron" class="size-4 rotate-180 rtl:rotate-0" /> {{ __('Back to models') }}
    </a>
    <x-page-header :title="$carModel->exists ? __('Edit model') : __('New model')" />

    <form method="POST" action="{{ $carModel->exists ? route('admin.car-models.update', $carModel) : route('admin.car-models.store') }}" class="card max-w-2xl space-y-5 p-6">
        @csrf
        @if ($carModel->exists)
            @method('PUT')
        @endif

        <div>
            <label for="brand_id" class="label">{{ __('Brand') }}</label>
            <select id="brand_id" name="brand_id" required class="input" data-searchable data-placeholder="{{ __('Choose a brand...') }}">
                <option value=""></option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand->id }}" @selected((int) old('brand_id', $carModel->brand_id) === $brand->id)>{{ $brand->name }}</option>
                @endforeach
            </select>
            @if ($brands->isEmpty())
                <p class="mt-1.5 text-sm text-amber-600">
                    {{ __('No brands yet.') }}
                    <a href="{{ route('admin.brands.create') }}" class="font-medium underline">{{ __('Create a brand') }}</a>
                </p>
            @endif
            @error('brand_id') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="name" class="label">{{ __('Model name') }}</label>
            <input id="name" name="name" type="text" value="{{ old('name', $carModel->name) }}" required class="input" placeholder="{{ __('e.g. Cerato') }}">
            @error('name') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.car-models.index') }}" class="btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="btn-primary">{{ $carModel->exists ? __('Save changes') : __('Create model') }}</button>
        </div>
    </form>
</x-layouts.app>
