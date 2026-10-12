<x-layouts.app :title="__('Settings')">
    <x-page-header :title="__('Settings')" />

    <form method="POST" action="{{ route('admin.settings.update') }}" class="card max-w-2xl space-y-6 p-6" x-data="{ sort: @js(old('catalogue_sort', $catalogueSort)) }">
        @csrf
        @method('PUT')

        <div>
            <h2 class="font-semibold text-slate-900">{{ __('Product catalogue') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Choose how products are ordered for clients on the products page.') }}</p>
        </div>

        <div>
            <span class="label">{{ __('Default sort order') }}</span>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach (\App\Models\Product::SORTS as $value => $label)
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition" :class="sort === '{{ $value }}' ? 'border-brand-500 bg-brand-50/50 ring-2 ring-brand-100' : 'border-slate-200 hover:bg-slate-50'">
                        <input type="radio" name="catalogue_sort" value="{{ $value }}" x-model="sort" class="text-brand-600">
                        <span class="text-sm font-medium text-slate-900">{{ __($label) }}</span>
                    </label>
                @endforeach
            </div>
            @error('catalogue_sort') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex cursor-pointer items-start gap-3 rounded-xl bg-slate-50 p-4">
            <input type="hidden" name="catalogue_client_sort" value="0">
            <input type="checkbox" name="catalogue_client_sort" value="1" @checked(old('catalogue_client_sort', $clientsCanSort)) class="mt-0.5 size-4 rounded border-slate-300 text-brand-600">
            <span>
                <span class="block text-sm font-semibold text-slate-900">{{ __('Let clients change the sort order') }}</span>
                <span class="block text-xs text-slate-500">{{ __('Shows a “Sort by” menu on the products page, starting from your default. When off, clients always see your order.') }}</span>
            </span>
        </label>

        <label class="flex cursor-pointer items-start gap-3 rounded-xl bg-slate-50 p-4">
            <input type="hidden" name="catalogue_show_out_of_stock" value="0">
            <input type="checkbox" name="catalogue_show_out_of_stock" value="1" @checked(old('catalogue_show_out_of_stock', $showsOutOfStock)) class="mt-0.5 size-4 rounded border-slate-300 text-brand-600">
            <span>
                <span class="block text-sm font-semibold text-slate-900">{{ __('Show out-of-stock products') }}</span>
                <span class="block text-xs text-slate-500">{{ __('When off, products with no stock are hidden from the store. Products whose stock isn’t tracked in Daftra stay visible.') }}</span>
            </span>
        </label>

        <div class="flex justify-end border-t border-slate-100 pt-5">
            <button type="submit" class="btn-primary">{{ __('Save changes') }}</button>
        </div>
    </form>
</x-layouts.app>
