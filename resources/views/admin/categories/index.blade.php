<x-layouts.app :title="__('Categories')">
    <x-page-header :title="__('Categories')" :subtitle="__('Part categories such as mirrors, doors, A/C or engine. A product can belong to several categories.')">
        <a href="{{ route('admin.categories.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('New category') }}</a>
    </x-page-header>

    <div class="card mb-6 p-4">
        <x-search-form :placeholder="__('Search by category name...')" />
    </div>

    <div class="card overflow-hidden">
        @if ($categories->isEmpty())
            <x-empty-state icon="squares" :title="__('No categories found')" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Products') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td>
                                    <span class="flex items-center gap-3 font-medium text-slate-900">
                                        <span class="grid size-10 shrink-0 place-items-center overflow-hidden rounded-lg bg-white ring-1 ring-slate-200">
                                            @if ($category->image_url)
                                                <img src="{{ $category->image_url }}" alt="" class="size-full object-contain p-0.5" loading="lazy">
                                            @else
                                                <x-icon name="squares" class="size-4 text-slate-300" />
                                            @endif
                                        </span>
                                        {{ $category->name }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.products.index', ['category' => $category->id]) }}" class="tabular-nums hover:text-brand-600">{{ number_format($category->products_count) }}</a>
                                </td>
                                <td class="text-end">
                                    <div class="inline-flex items-center gap-1">
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900" title="{{ __('Edit') }}"><x-icon name="pencil" class="size-4" /></a>
                                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" x-data @submit="if (! window.confirm(@js(__('Delete this category? Its products will not be deleted.')))) $event.preventDefault()">
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
            <div class="border-t border-slate-100 px-4 py-3">{{ $categories->links() }}</div>
        @endif
    </div>
</x-layouts.app>
