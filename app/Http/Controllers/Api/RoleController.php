<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Role::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'slug' => 'nullable|string|max:255|unique:roles,slug',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);

        // Generate slug from name if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $validated['name']));
        }

        return Role::create($validated);
    }

    public function show(Role $role)
    {
        return $role;
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'string|max:255|unique:roles,name,' . $role->id,
            'slug' => 'string|max:255|unique:roles,slug,' . $role->id,
            'description' => 'string',
            'permissions' => 'array',
        ]);

        // Generate slug from name if name is provided and slug is not
        if (isset($validated['name']) && !isset($validated['slug'])) {
            $validated['slug'] = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $validated['name']));
        }

        $role->update($validated);

        return $role;
    }

    public function destroy(Role $role)
    {
        // Prevent deletion if users have this role
        $userCount = \App\Models\User::where('role', $role->slug)->count();
        if ($userCount > 0) {
            return response()->json([
                'message' => "Cannot delete role. {$userCount} user(s) have this role.",
            ], 422);
        }

        $role->delete();
        return response()->noContent();
    }
}
