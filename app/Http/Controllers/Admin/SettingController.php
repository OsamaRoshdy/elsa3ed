<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', [
            'catalogueSort' => Setting::get('catalogue_sort', Product::DEFAULT_SORT),
            'clientsCanSort' => (bool) Setting::get('catalogue_client_sort', true),
            'showsOutOfStock' => Product::storeShowsOutOfStock(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'catalogue_sort' => ['required', Rule::in(array_keys(Product::SORTS))],
            'catalogue_client_sort' => ['nullable', 'boolean'],
            'catalogue_show_out_of_stock' => ['nullable', 'boolean'],
        ]);

        Setting::set('catalogue_sort', $validated['catalogue_sort']);
        Setting::set('catalogue_client_sort', $request->boolean('catalogue_client_sort') ? '1' : '0');
        Setting::set('catalogue_show_out_of_stock', $request->boolean('catalogue_show_out_of_stock') ? '1' : '0');

        return back()->with('success', __('Settings saved.'));
    }
}
