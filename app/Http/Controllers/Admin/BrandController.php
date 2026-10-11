<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        $brands = Brand::query()
            ->withCount(['products', 'carModels'])
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.brands.index', ['brands' => $brands, 'filters' => $filters]);
    }

    public function create(): View
    {
        return view('admin.brands.form', ['brand' => new Brand]);
    }

    public function store(Request $request): RedirectResponse
    {
        $brand = new Brand;
        $this->save($request, $brand);

        return redirect()->route('admin.brands.index')->with('success', __('Brand created successfully.'));
    }

    public function edit(Brand $brand): View
    {
        return view('admin.brands.form', ['brand' => $brand]);
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $this->save($request, $brand);

        return redirect()->route('admin.brands.index')->with('success', __('Brand updated successfully.'));
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();

        return redirect()->route('admin.brands.index')->with('success', __('Brand deleted.'));
    }

    /**
     * Saves the name and replaces or removes the logo (the old file is deleted from disk).
     */
    private function save(Request $request, Brand $brand): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('brands', 'name')->ignore($brand)],
            // SVG is not accepted: it can carry scripts and is served from our own domain.
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $brand->name = $data['name'];

        if ($request->hasFile('logo') || $request->boolean('remove_logo')) {
            $brand->deleteLogoFile();
            $brand->logo_path = $request->file('logo')?->store('brands', Brand::LOGO_DISK);
        }

        $brand->save();
    }
}
