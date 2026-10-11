<x-layouts.app :title="__('Models')">
    <x-page-header :title="__('Models')" :subtitle="__('Vehicle models of each brand, e.g. Kia → Cerato. Products are linked to models with the years they fit.')">
        <a href="{{ route('admin.car-models.create', ['brand' => $filters['brand'] ?? null]) }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('New model') }}</a>
    </x-page-header>

    <div class="card mb-6 p-4">
        <x-search-form :placeholder="__('Search by model name...')">
            @if ($brands->isNotEmpty())
                <select name="brand" class="input sm:w-52" onchange="this.form.submit()">
                    <option value="">{{ __('All brands') }}</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}" @selected((int) ($filters['brand'] ?? 0) === $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            @endif
        </x-search-form>
    </div>

    <div class="card overflow-hidden">
        @if ($carModels->isEmpty())
            <x-empty-state icon="car" :title="__('No models found')" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Brand') }}</th>
                            <th>{{ __('Model') }}</th>
                            <th>{{ __('Products') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($carModels as $carModel)
                            <tr>
                                <td>
                                    <span class="flex items-center gap-2">
                                        <x-brand-logo :brand="$carModel->brand" class="size-7 rounded-md ring-1 ring-slate-200" />
                                        {{ $carModel->brand->name }}
                                    </span>
                                </td>
                                <td class="font-medium text-slate-900">{{ $carModel->name }}</td>
                                <td class="tabular-nums">{{ number_format($carModel->fitments_count) }}</td>
                                <td class="text-end">
                                    <div class="inline-flex items-center gap-1">
                                        <a href="{{ route('admin.car-models.edit', $carModel) }}" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900" title="{{ __('Edit') }}"><x-icon name="pencil" class="size-4" /></a>
                                        <form method="POST" action="{{ route('admin.car-models.destroy', $carModel) }}" x-data @submit="if (! window.confirm(@js(__('Delete this model? Products will no longer be linked to it.')))) $event.preventDefault()">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg p-2 text-slate-500 hover:bg-rose-50 hover:text-rose-600" title="{{ __('Delete') }}"><x-icon name="trash" class="size-4" /></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">{{ $carModels->links() }}</div>
        @endif
    </div>
</x-layouts.app>
