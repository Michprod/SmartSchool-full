<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeeRate;
use App\Models\FeeType;
use App\Models\InstallmentType;
use Illuminate\Http\Request;

class FinanceConfigController extends Controller
{
    public function index()
    {
        return response()->json([
            'fee_types' => FeeType::orderBy('label')->get(),
            'installment_types' => InstallmentType::orderBy('label')->get(),
            'fee_rates' => FeeRate::with(['feeType', 'gradeLevel'])->latest()->get(),
        ]);
    }

    public function storeFeeType(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:64|unique:fee_types,code',
            'label' => 'required|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        return response()->json(FeeType::create($validated), 201);
    }

    public function storeInstallmentType(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:64|unique:installment_types,code',
            'label' => 'required|string|max:255',
            'default_count' => 'required|integer|min:1|max:24',
            'is_active' => 'sometimes|boolean',
        ]);

        return response()->json(InstallmentType::create($validated), 201);
    }

    public function storeFeeRate(Request $request)
    {
        $validated = $request->validate([
            'fee_type_id' => 'required|exists:fee_types,id',
            'academic_year' => 'required|string|max:20',
            'currency' => 'required|in:CDF,USD',
            'amount' => 'required|numeric|min:0',
            'grade_level_id' => 'nullable|exists:grade_levels,id',
            'is_active' => 'sometimes|boolean',
        ]);

        return response()->json(FeeRate::create($validated)->load(['feeType', 'gradeLevel']), 201);
    }
}
