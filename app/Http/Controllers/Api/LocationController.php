<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
}
