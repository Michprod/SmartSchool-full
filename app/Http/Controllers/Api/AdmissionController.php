<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use Illuminate\Http\Request;

class AdmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Admission::with('reviewer');
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        return response()->json($query->orderByDesc('submitted_at')->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_first_name'    => 'required|string|max:255',
            'student_last_name'     => 'required|string|max:255',
            'student_date_of_birth' => 'required|date',
            'student_gender'        => 'required|in:M,F',
            'parent_first_name'     => 'required|string|max:255',
            'parent_last_name'      => 'required|string|max:255',
            'parent_email'          => 'required|email',
            'parent_phone'          => 'required|string|max:50',
            'applied_class'         => 'required|string|max:255',
            'documents'             => 'nullable|array',
            'notes'                 => 'nullable|string',
        ]);
        $validated['submitted_at'] = now();

        $admission = Admission::create($validated);
        return response()->json($admission, 201);
    }

    public function show(string $id)
    {
        return response()->json(Admission::with('reviewer')->findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $admission = Admission::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:submitted,under_review,accepted,rejected',
            'notes'  => 'nullable|string',
        ]);

        // Set reviewer info when decision is made
        if (in_array($validated['status'], ['accepted', 'rejected'])) {
            $validated['reviewed_by'] = $request->user()->id;
            $validated['reviewed_at'] = now();
        }

        $admission->update($validated);
        
        if ($validated['status'] === 'accepted') {
            // Check if student exists to avoid duplicates
            if (!\App\Models\Student::where('first_name', $admission->student_first_name)->where('last_name', $admission->student_last_name)->exists()) {
                // Determine class ID
                $schoolClass = \App\Models\SchoolClass::where('name', $admission->applied_class)->first();
                $classId = $schoolClass ? $schoolClass->id : (\App\Models\SchoolClass::first()->id ?? null);
                
                if ($classId) {
                    \App\Models\Student::create([
                        'first_name' => $admission->student_first_name,
                        'last_name' => $admission->student_last_name,
                        'date_of_birth' => $admission->student_date_of_birth,
                        'gender' => $admission->student_gender,
                        'guardian_name' => $admission->parent_first_name . ' ' . $admission->parent_last_name,
                        'guardian_phone' => $admission->parent_phone,
                        'status' => 'active',
                        'is_active' => true,
                        'class_id' => $classId,
                        'enrollment_date' => now(),
                        'matricule' => 'STU-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                        'student_number' => 'STU-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                    ]);
                }
            }
        }

        return response()->json($admission->fresh('reviewer'));
    }

    public function destroy(string $id)
    {
        Admission::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
