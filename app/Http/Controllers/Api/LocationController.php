<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeRate;
use App\Models\FeeType;
use App\Models\InstallmentType;
use App\Models\RdcCity;
use App\Models\RdcCommune;
use App\Models\RdcProvince;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function provinces()
    {
        return response()->json(
            RdcProvince::orderBy('name')->get(['id', 'name', 'code'])
        );
    }

    public function cities(Request $request)
    {
        $request->validate(['province_id' => 'required|exists:rdc_provinces,id']);

        return response()->json(
            RdcCity::where('province_id', $request->province_id)
                ->orderBy('name')
                ->get(['id', 'name', 'province_id'])
        );
    }

    public function communes(Request $request)
    {
        $request->validate(['city_id' => 'required|exists:rdc_cities,id']);

        return response()->json(
            RdcCommune::where('city_id', $request->city_id)
                ->orderBy('name')
                ->get(['id', 'name', 'city_id'])
        );
    }

    public function storeProvince(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:rdc_provinces,name',
            'code' => 'nullable|string|max:16',
        ]);

        return response()->json(RdcProvince::create($validated), 201);
    }

    public function updateProvince(Request $request, RdcProvince $province)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:rdc_provinces,name,' . $province->id,
            'code' => 'nullable|string|max:16',
        ]);

        $province->update($validated);

        return response()->json($province);
    }

    public function destroyProvince(RdcProvince $province)
    {
        $province->delete();

        return response()->json(null, 204);
    }

    public function storeCity(Request $request)
    {
        $validated = $request->validate([
            'province_id' => 'required|exists:rdc_provinces,id',
            'name' => 'required|string|max:255',
        ]);

        $city = RdcCity::create($validated);

        return response()->json($city, 201);
    }

    public function updateCity(Request $request, RdcCity $city)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
        ]);

        $city->update($validated);

        return response()->json($city);
    }

    public function destroyCity(RdcCity $city)
    {
        $city->delete();

        return response()->json(null, 204);
    }

    public function storeCommune(Request $request)
    {
        $validated = $request->validate([
            'city_id' => 'required|exists:rdc_cities,id',
            'name' => 'required|string|max:255',
        ]);

        $commune = RdcCommune::create($validated);

        return response()->json($commune, 201);
    }

    public function updateCommune(Request $request, RdcCommune $commune)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
        ]);

        $commune->update($validated);

        return response()->json($commune);
    }

    public function destroyCommune(RdcCommune $commune)
    {
        $commune->delete();

        return response()->json(null, 204);
    }
}
