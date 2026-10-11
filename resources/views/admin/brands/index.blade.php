<x-layouts.app :title="__('Brands')">
    <x-page-header :title="__('Brands')" :subtitle="__('Car brands such as Kia, Nissan or Hyundai. A product can belong to several brands.')">
        <a href="{{ route('admin.brands.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('New brand') }}</a>
    </x-page-header>

    <div class="card mb-6 p-4">
        <x-search-form :placeholder="__('Search by brand name...')" />
    </div>

    <div class="card overflow-hidden">
        @if ($brands->isEmpty())
            <x-empty-state icon="tag" :title="__('No brands found')" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Models') }}</th>
                            <th>{{ __('Products') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($brands as $brand)
                            <tr>
                                <td>
                                    <span class="flex items-center gap-3 font-medium text-slate-900">
                                        <x-brand-logo :brand="$brand" class="size-10 rounded-lg ring-1 ring-slate-200" />
                                        {{ $brand->name }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.car-models.index', ['brand' => $brand->id]) }}" class="tabular-nums hover:text-brand-600">{{ number_format($brand->car_models_count) }}</a>
                                </td>
                                <td>
                                    <a href="{{ route('admin.products.index', ['brand' => $brand->id]) }}" class="tabular-nums hover:text-brand-600">{{ number_format($brand->products_count) }}</a>
                                </td>
                                <td class="text-end">
                                    <div class="inline-flex items-center gap-1">
                                        <a href="{{ route('admin.brands.edit', $brand) }}" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900" title="{{ __('Edit') }}"><x-icon name="pencil" class="size-4" /></a>
                                        <form method="POST" action="{{ route('admin.brands.destroy', $brand) }}" x-data @submit="if (! window.confirm(@js(__('Delete this brand and its models? Its products will not be deleted.')))) $event.preventDefault()">
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
            <div class="border-t border-slate-100 px-4 py-3">{{ $brands->links() }}</div>
        @endif
    </div>
</x-layouts.app>
