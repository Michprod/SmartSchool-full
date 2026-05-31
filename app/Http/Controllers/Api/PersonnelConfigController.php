<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonnelConfigItem;
use Illuminate\Http\Request;

class PersonnelConfigController extends Controller
{
    private const VALID_TYPES = ['department', 'job_grade', 'contract_type'];

    public function index(Request $request)
    {
        $type = $request->string('type')->toString();

        $query = PersonnelConfigItem::query()->where('is_active', true)->orderBy('label');

        if ($type && in_array($type, self::VALID_TYPES, true)) {
            $query->where('type', $type);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:' . implode(',', self::VALID_TYPES),
            'label' => 'required|string|max:255',
            'code' => 'nullable|string|max:64',
            'is_active' => 'sometimes|boolean',
        ]);

        $item = PersonnelConfigItem::create($validated);

        return response()->json($item, 201);
    }

    public function destroy(PersonnelConfigItem $item)
    {
        $item->update(['is_active' => false]);

        return response()->json(null, 204);
    }
}
