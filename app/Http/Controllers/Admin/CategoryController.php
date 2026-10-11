<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        $categories = Category::query()
            ->withCount('products')
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.categories.index', ['categories' => $categories, 'filters' => $filters]);
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->save($request, new Category);

        return redirect()->route('admin.categories.index')->with('success', __('Category created successfully.'));
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', ['category' => $category]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->save($request, $category);

        return redirect()->route('admin.categories.index')->with('success', __('Category updated successfully.'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', __('Category deleted.'));
    }

    /**
     * Saves the name and replaces or removes the image (the old file is deleted from disk).
     */
    private function save(Request $request, Category $category): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category)],
            // SVG is not accepted: it can carry scripts and is served from our own domain.
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        $category->name = $data['name'];

        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            $category->deleteImageFile();
            $category->image_path = $request->file('image')?->store('categories', Category::IMAGE_DISK);
        }

        $category->save();
    }
}
