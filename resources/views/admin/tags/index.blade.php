<x-layouts.app :title="__('Tags')">
    <x-page-header :title="__('Tags')" :subtitle="__('Labels such as Original or Korea aftermarket. A product can have several tags.')">
        <a href="{{ route('admin.tags.create') }}" class="btn-primary"><x-icon name="plus" class="size-4" /> {{ __('New tag') }}</a>
    </x-page-header>

    <div class="card mb-6 p-4">
        <x-search-form :placeholder="__('Search by tag name...')" />
    </div>

    <div class="card overflow-hidden">
        @if ($tags->isEmpty())
            <x-empty-state icon="badge" :title="__('No tags found')" />
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
                        @foreach ($tags as $tag)
                            <tr>
                                <td><x-tag-badges :tags="collect([$tag])" /></td>
                                <td>
                                    <a href="{{ route('admin.products.index', ['tag' => $tag->id]) }}" class="tabular-nums hover:text-brand-600">{{ number_format($tag->products_count) }}</a>
                                </td>
                                <td class="text-end">
                                    <div class="inline-flex items-center gap-1">
                                        <a href="{{ route('admin.tags.edit', $tag) }}" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900" title="{{ __('Edit') }}"><x-icon name="pencil" class="size-4" /></a>
                                        <form method="POST" action="{{ route('admin.tags.destroy', $tag) }}" x-data @submit="if (! window.confirm(@js(__('Delete this tag? Its products will not be deleted.')))) $event.preventDefault()">
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
            <div class="border-t border-slate-100 px-4 py-3">{{ $tags->links() }}</div>
        @endif
    </div>
</x-layouts.app>
