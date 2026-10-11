<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CarModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CarModelController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'integer'],
        ]);

        $carModels = CarModel::query()
            ->with('brand')
            ->withCount('fitments')
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('name', 'like', "%{$term}%"))
            ->when($filters['brand'] ?? null, fn ($query, $brandId) => $query->where('brand_id', $brandId))
            ->orderBy(Brand::select('name')->whereColumn('brands.id', 'car_models.brand_id'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.car-models.index', [
            'carModels' => $carModels,
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.car-models.form', [
            'carModel' => new CarModel(['brand_id' => $request->integer('brand') ?: null]),
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $carModel = CarModel::create($this->validated($request));

        return redirect()->route('admin.car-models.index', ['brand' => $carModel->brand_id])->with('success', __('Model created successfully.'));
    }

    public function edit(CarModel $carModel): View
    {
        return view('admin.car-models.form', [
            'carModel' => $carModel,
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, CarModel $carModel): RedirectResponse
    {
        $carModel->update($this->validated($request, $carModel));

        return redirect()->route('admin.car-models.index', ['brand' => $carModel->brand_id])->with('success', __('Model updated successfully.'));
    }

    public function destroy(CarModel $carModel): RedirectResponse
    {
        $carModel->delete();

        return redirect()->route('admin.car-models.index', ['brand' => $carModel->brand_id])->with('success', __('Model deleted.'));
    }

    /**
     * @return array{brand_id: int, name: string}
     */
    private function validated(Request $request, ?CarModel $carModel = null): array
    {
        return $request->validate([
            'brand_id' => ['required', 'integer', Rule::exists('brands', 'id')],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('car_models', 'name')->where('brand_id', $request->integer('brand_id'))->ignore($carModel),
            ],
        ]);
    }
}
