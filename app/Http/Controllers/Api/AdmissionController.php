<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;

class AdmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Admission::with(['reviewer', 'appliedSchoolClass']);
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderByDesc('submitted_at')->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_first_name' => 'required|string|max:255',
            'student_last_name' => 'required|string|max:255',
            'student_date_of_birth' => 'required|date',
            'student_gender' => 'required|in:M,F',
            'parent_first_name' => 'required|string|max:255',
            'parent_last_name' => 'required|string|max:255',
            'parent_email' => 'required|email',
            'parent_phone' => 'required|string|max:50',
            'applied_class_id' => 'nullable|exists:school_classes,id',
            'applied_class' => 'nullable|string|max:255',
            'documents' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        if (empty($validated['applied_class_id']) && empty($validated['applied_class'])) {
            return response()->json(['message' => 'Classe demandée requise.'], 422);
        }

        if (! empty($validated['applied_class_id'])) {
            $class = SchoolClass::find($validated['applied_class_id']);
            $validated['applied_class'] = $class?->display_name ?? $class?->name;
        }

        $validated['submitted_at'] = now();

        $admission = Admission::create($validated);

        return response()->json($admission->load('appliedSchoolClass'), 201);
    }

    public function show(string $id)
    {
        return response()->json(Admission::with(['reviewer', 'appliedSchoolClass'])->findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $admission = Admission::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:submitted,under_review,accepted,rejected',
            'notes' => 'nullable|string',
        ]);

        if (in_array($validated['status'], ['accepted', 'rejected'])) {
            $validated['reviewed_by'] = $request->user()->id;
            $validated['reviewed_at'] = now();
        }

        $admission->update($validated);

        if ($validated['status'] === 'accepted') {
            if (! Student::where('first_name', $admission->student_first_name)
                ->where('last_name', $admission->student_last_name)
                ->exists()) {
                $classId = $admission->applied_class_id
                    ?? SchoolClass::where('name', $admission->applied_class)
                        ->orWhere('display_name', $admission->applied_class)
                        ->value('id')
                    ?? SchoolClass::query()->value('id');

                if ($classId) {
                    Student::create([
                        'first_name' => $admission->student_first_name,
                        'last_name' => $admission->student_last_name,
                        'date_of_birth' => $admission->student_date_of_birth,
                        'gender' => $admission->student_gender,
                        'guardian_name' => $admission->parent_first_name.' '.$admission->parent_last_name,
                        'guardian_phone' => $admission->parent_phone,
                        'status' => 'active',
                        'is_active' => true,
                        'class_id' => $classId,
                        'enrollment_date' => now(),
                        'matricule' => 'STU-'.date('Ymd').'-'.str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT),
                        'student_number' => 'STU-'.date('Ymd').'-'.str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT),
                    ]);
                }
            }
        }

        return response()->json($admission->fresh(['reviewer', 'appliedSchoolClass']));
    }

    public function destroy(string $id)
    {
        Admission::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
