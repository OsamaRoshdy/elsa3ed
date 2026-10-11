<x-layouts.app :title="__('Edit product')">
    <a href="{{ route('admin.products.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-900">
        <x-icon name="chevron" class="size-4 rotate-180 rtl:rotate-0" /> {{ __('Back to products') }}
    </a>
    <x-page-header :title="$product->name" :subtitle="__('Other product details come from Daftra and are updated on every sync.')" />

    <form method="POST" action="{{ route('admin.products.update', $product) }}" class="card max-w-2xl space-y-5 p-6">
        @csrf
        @method('PUT')

        @foreach ([
            'categories' => [__('Categories'), __('Choose one or more categories...'), $categories, route('admin.categories.create'), __('No categories yet.'), __('Create a category')],
            'brands' => [__('Brands'), __('Choose one or more brands...'), $brands, route('admin.brands.create'), __('No brands yet.'), __('Create a brand')],
            'tags' => [__('Tags'), __('Choose one or more tags...'), $tags, route('admin.tags.create'), __('No tags yet.'), __('Create a tag')],
        ] as $field => [$label, $placeholder, $options, $createUrl, $emptyText, $createText])
            @php
                $selected = collect(old($field, $product->{$field}->modelKeys()))->map(fn ($id) => (int) $id);
            @endphp
            <div>
                <label for="{{ $field }}" class="label">{{ $label }}</label>
                <select id="{{ $field }}" name="{{ $field }}[]" multiple class="input" data-searchable data-placeholder="{{ $placeholder }}">
                    @foreach ($options as $option)
                        <option value="{{ $option->id }}" @selected($selected->contains($option->id))>{{ $option->name }}</option>
                    @endforeach
                </select>
                @if ($options->isEmpty())
                    <p class="mt-1.5 text-sm text-amber-600">
                        {{ $emptyText }}
                        <a href="{{ $createUrl }}" class="font-medium underline">{{ $createText }}</a>
                    </p>
                @endif
                @error($field) <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                @error($field.'.*') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
        @endforeach

        @php
            $fitmentRows = collect(old('fitments', $product->fitments->map(fn ($fitment) => $fitment->only(['car_model_id', 'year_from', 'year_to']))->all()))
                ->map(fn ($row) => ['car_model_id' => (string) ($row['car_model_id'] ?? ''), 'year_from' => $row['year_from'] ?? '', 'year_to' => $row['year_to'] ?? ''])
                ->values();
            $hasCarModels = $brands->contains(fn ($brand) => $brand->carModels->isNotEmpty());
        @endphp
        <div x-data="{
            rows: @js($fitmentRows),
            errors: @js($errors->getMessages()),
            add() { this.rows.push({ car_model_id: '', year_from: '', year_to: '' }) },
            error(index, field) { return (this.errors[`fitments.${index}.${field}`] ?? [])[0] ?? null },
        }">
            <div class="flex items-center justify-between gap-3">
                <span class="label mb-0">{{ __('Fits vehicles') }}</span>
                <button type="button" class="btn-secondary px-3 py-1.5 text-sm" @click="add()" @disabled(! $hasCarModels)>
                    <x-icon name="plus" class="size-4" /> {{ __('Add vehicle') }}
                </button>
            </div>
            <p class="mt-1 text-xs text-slate-500">{{ __('Choose the model and the years it fits, e.g. Kia Cerato from 2015 to 2017. Leave a year empty for no limit. The model\'s brand is added to the product automatically.') }}</p>

            @unless ($hasCarModels)
                <p class="mt-2 text-sm text-amber-600">
                    {{ __('No models yet.') }}
                    <a href="{{ route('admin.car-models.create') }}" class="font-medium underline">{{ __('Create a model') }}</a>
                </p>
            @endunless

            <p class="mt-3 text-sm text-slate-400" x-show="rows.length === 0" x-cloak>{{ __('This product is not linked to any vehicle yet.') }}</p>

            <div class="mt-3 space-y-3">
                <template x-for="(row, index) in rows" :key="index">
                    <div class="rounded-xl bg-slate-50 p-3 ring-1 ring-slate-200">
                        <div class="grid gap-3 sm:grid-cols-[1fr_7rem_7rem_auto] sm:items-end">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('Model') }}</label>
                                <select class="input" :name="`fitments[${index}][car_model_id]`" x-model="row.car_model_id" required>
                                    <option value="">{{ __('Choose a model...') }}</option>
                                    @foreach ($brands as $brand)
                                        @if ($brand->carModels->isNotEmpty())
                                            <optgroup label="{{ $brand->name }}">
                                                @foreach ($brand->carModels as $carModel)
                                                    <option value="{{ $carModel->id }}">{{ $brand->name }} {{ $carModel->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('From year') }}</label>
                                <input type="number" class="input" dir="ltr" :name="`fitments[${index}][year_from]`" x-model="row.year_from" min="{{ \App\Models\Fitment::FIRST_YEAR }}" max="{{ \App\Models\Fitment::lastYear() }}" placeholder="{{ __('Any') }}">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-500">{{ __('To year') }}</label>
                                <input type="number" class="input" dir="ltr" :name="`fitments[${index}][year_to]`" x-model="row.year_to" min="{{ \App\Models\Fitment::FIRST_YEAR }}" max="{{ \App\Models\Fitment::lastYear() }}" placeholder="{{ __('Any') }}">
                            </div>
                            <button type="button" class="rounded-lg p-2.5 text-slate-500 hover:bg-rose-50 hover:text-rose-600" title="{{ __('Remove') }}" @click="rows.splice(index, 1); errors = {}">
                                <x-icon name="trash" class="size-4" />
                            </button>
                        </div>
                        <template x-for="field in ['car_model_id', 'year_from', 'year_to']">
                            <p class="mt-1.5 text-sm text-rose-600" x-show="error(index, field)" x-text="error(index, field)"></p>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        @if ($product->category)
            <p class="text-xs text-slate-500">{{ __('Daftra category: :category', ['category' => $product->category]) }}</p>
        @endif

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.products.index') }}" class="btn-secondary">{{ __('Cancel') }}</a>
            <button type="submit" class="btn-primary">{{ __('Save changes') }}</button>
        </div>
    </form>
</x-layouts.app>
