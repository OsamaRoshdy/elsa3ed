<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TagController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        $tags = Tag::query()
            ->withCount('products')
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.tags.index', ['tags' => $tags, 'filters' => $filters]);
    }

    public function create(): View
    {
        return view('admin.tags.form', ['tag' => new Tag(['color' => Tag::DEFAULT_COLOR])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Tag::create($this->validated($request));

        return redirect()->route('admin.tags.index')->with('success', __('Tag created successfully.'));
    }

    public function edit(Tag $tag): View
    {
        return view('admin.tags.form', ['tag' => $tag]);
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $tag->update($this->validated($request, $tag));

        return redirect()->route('admin.tags.index')->with('success', __('Tag updated successfully.'));
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();

        return redirect()->route('admin.tags.index')->with('success', __('Tag deleted.'));
    }

    /**
     * @return array{name: string, color: string}
     */
    private function validated(Request $request, ?Tag $tag = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('tags', 'name')->ignore($tag)],
            'color' => ['required', Rule::in(array_keys(Tag::COLORS))],
        ]);
    }
}
