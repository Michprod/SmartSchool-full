<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryItem::query();
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'location' => 'nullable|string|max:255',
            'status'   => 'required|in:in_stock,in_use,under_maintenance',
        ]);
        $item = InventoryItem::create($validated);
        return response()->json($item, 201);
    }

    public function show(string $id)
    {
        return response()->json(InventoryItem::findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $item = InventoryItem::findOrFail($id);
        $validated = $request->validate([
            'name'     => 'string|max:255',
            'category' => 'string|max:255',
            'quantity' => 'integer|min:0',
            'location' => 'nullable|string',
            'status'   => 'in:in_stock,in_use,under_maintenance',
        ]);
        $item->update($validated);
        return response()->json($item);
    }

    public function destroy(string $id)
    {
        InventoryItem::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
