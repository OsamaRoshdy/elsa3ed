<x-layouts.app :title="__('Products')">
    <x-page-header :title="__('Products')" :subtitle="__(':count products synced from Daftra', ['count' => number_format($products->total())])">
        <a href="{{ route('products.index') }}" class="btn-secondary" target="_blank"><x-icon name="globe" class="size-4" /> {{ __('Open catalogue') }}</a>
    </x-page-header>

    <div class="card mb-6 p-4">
        <x-search-form :placeholder="__('Search by product name or SKU...')">
            @if ($categories->isNotEmpty())
                <select name="category" class="input sm:w-52" onchange="this.form.submit()">
                    <option value="">{{ __('All categories') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) ($filters['category'] ?? 0) === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            @endif
            @if ($brands->isNotEmpty())
                <select name="brand" class="input sm:w-44" onchange="this.form.submit()">
                    <option value="">{{ __('All brands') }}</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}" @selected((int) ($filters['brand'] ?? 0) === $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            @endif
            @if ($tags->isNotEmpty())
                <select name="tag" class="input sm:w-44" onchange="this.form.submit()">
                    <option value="">{{ __('All tags') }}</option>
                    @foreach ($tags as $tag)
                        <option value="{{ $tag->id }}" @selected((int) ($filters['tag'] ?? 0) === $tag->id)>{{ $tag->name }}</option>
                    @endforeach
                </select>
            @endif
            <label class="flex items-center gap-2 text-sm whitespace-nowrap text-slate-600">
                <input type="checkbox" name="low" value="1" @checked($filters['low'] ?? false) onchange="this.form.submit()" class="size-4 rounded border-slate-300 text-brand-600">
                {{ __('Low stock only') }}
            </label>
        </x-search-form>
    </div>

    <div class="card overflow-hidden">
        @if ($products->isEmpty())
            <x-empty-state icon="cube" :title="__('No products found')" :description="__('Run a sync to import products from Daftra.')" />
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th>SKU</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Brands') }}</th>
                            <th>{{ __('Price') }}</th>
                            <th>{{ __('Quantity') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Last synchronized') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td>
                                    <a href="{{ route('products.show', $product) }}" class="flex items-center gap-3 font-medium text-slate-900 hover:text-brand-600">
                                        <span class="size-10 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                                            @if ($product->image)
                                                <img src="{{ $product->image }}" alt="" class="size-full object-cover" loading="lazy">
                                            @else
                                                <span class="grid size-full place-items-center text-slate-300"><x-icon name="photo" class="size-4" /></span>
                                            @endif
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block max-w-xs truncate">{{ $product->name }}</span>
                                            <x-tag-badges :tags="$product->tags" class="mt-1" />
                                        </span>
                                        @unless ($product->is_active)
                                            <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-xs text-slate-500">{{ __('Inactive') }}</span>
                                        @endunless
                                    </a>
                                </td>
                                <td class="text-slate-500" dir="ltr">{{ $product->sku ?? '—' }}</td>
                                <td>
                                    @if ($product->categories->isEmpty())
                                        <span class="text-slate-400">—</span>
                                    @else
                                        <span class="block max-w-48 text-sm">{{ $product->categories->pluck('name')->join('، ') }}</span>
                                    @endif
                                    @if ($product->category)
                                        <span class="block text-xs text-slate-400" title="{{ __('Daftra category') }}">{{ $product->category }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($product->brands->isEmpty())
                                        <span class="text-slate-400">—</span>
                                    @else
                                        <x-brand-badges :brands="$product->brands" class="max-w-48" />
                                    @endif
                                </td>
                                <td><x-money :amount="$product->price" /></td>
                                <td class="font-semibold tabular-nums">{{ $product->stock_quantity === null ? '—' : (float) $product->stock_quantity + 0 }}</td>
                                <td><x-stock-badge :product="$product" /></td>
                                <td class="text-xs text-slate-500">{{ $product->last_synced_at?->diffForHumans() ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.products.edit', $product) }}" class="inline-flex rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900" title="{{ __('Edit categories and brands') }}"><x-icon name="pencil" class="size-4" /></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">{{ $products->links() }}</div>
        @endif
    </div>
</x-layouts.app>
