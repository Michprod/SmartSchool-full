<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = \App\Models\Student::with('schoolClass');

        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhere('matricule', 'like', "%$search%");
            });
        }

        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'matricule' => 'required|string|unique:students',
            'student_number' => 'required|string|unique:students',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:M,F',
            'class_id' => 'required|exists:school_classes,id',
            'enrollment_date' => 'required|date',
            'guardian_name' => 'required|string',
            'guardian_phone' => 'required|string',
            // Optionnels
            'parent_ids' => 'nullable|array',
            'medical_info' => 'nullable|array',
            'status' => 'string|in:active,inactive,suspended',
        ]);

        $student = \App\Models\Student::create($validated);
        return response()->json($student, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $student = \App\Models\Student::with('schoolClass')->findOrFail($id);
        return response()->json($student);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $student = \App\Models\Student::findOrFail($id);

        $validated = $request->validate([
            'matricule' => 'string|unique:students,matricule,' . $id,
            'student_number' => 'string|unique:students,student_number,' . $id,
            'first_name' => 'string|max:255',
            'last_name' => 'string|max:255',
            'date_of_birth' => 'date',
            'gender' => 'in:M,F',
            'class_id' => 'exists:school_classes,id',
            'enrollment_date' => 'date',
            'status' => 'string|in:active,inactive,suspended',
        ]);

        $student->update($validated);
        return response()->json($student);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $student = \App\Models\Student::findOrFail($id);
        $student->delete();
        return response()->json(null, 204);
    }
}
