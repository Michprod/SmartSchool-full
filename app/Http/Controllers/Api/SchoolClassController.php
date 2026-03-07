<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SchoolClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(\App\Models\SchoolClass::withCount('students')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'required|string|max:255',
            'capacity' => 'required|integer|min:0',
            'teacher_id' => 'nullable|exists:users,id',
            'schedule' => 'nullable|array',
        ]);

        $class = \App\Models\SchoolClass::create($validated);
        return response()->json($class, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $class = \App\Models\SchoolClass::with(['teacher', 'students'])->findOrFail($id);
        return response()->json($class);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $class = \App\Models\SchoolClass::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'string|max:255',
            'level' => 'string|max:255',
            'capacity' => 'integer|min:0',
            'teacher_id' => 'nullable|exists:users,id',
            'schedule' => 'nullable|array',
        ]);

        $class->update($validated);
        return response()->json($class);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $class = \App\Models\SchoolClass::findOrFail($id);
        $class->delete();
        return response()->json(null, 204);
    }
}
