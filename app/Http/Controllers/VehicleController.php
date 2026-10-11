<?php

namespace App\Http\Controllers;

use App\Models\Fitment;
use App\Support\Garage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * "Select your car": remembers the visitor's car in the session.
 */
class VehicleController extends Controller
{
    public function store(Request $request, Garage $garage): RedirectResponse
    {
        $data = $request->validate([
            'brand' => ['required', 'integer', Rule::exists('brands', 'id')],
            'model' => ['nullable', 'integer', Rule::exists('car_models', 'id')->where('brand_id', $request->integer('brand'))],
            'year' => ['nullable', 'integer', 'min:'.Fitment::FIRST_YEAR, 'max:'.Fitment::lastYear()],
            'stay' => ['nullable', 'boolean'],
        ]);

        $garage->select((int) $data['brand'], isset($data['model']) ? (int) $data['model'] : null, isset($data['year']) ? (int) $data['year'] : null);

        // "stay" keeps the visitor on the current page (e.g. checking a product's fitment).
        if ($request->boolean('stay')) {
            return back();
        }

        return redirect()->route('products.index', $garage->query());
    }

    public function destroy(Garage $garage): RedirectResponse
    {
        $garage->clear();

        return back();
    }
}
