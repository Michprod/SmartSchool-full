<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\RdcCity;
use App\Models\RdcCommune;
use App\Models\RdcProvince;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Student::with('schoolClass');

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

        return StudentResource::collection(
            $query->orderBy('last_name')->orderBy('first_name')->paginate($request->integer('per_page', 15))
        );
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
            'place_of_birth' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:255',
            'blood_group' => 'nullable|string|max:10',
            'photo' => 'nullable|string|max:2048',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'province_id' => 'nullable|exists:rdc_provinces,id',
            'city_id' => 'nullable|exists:rdc_cities,id',
            'commune_id' => 'nullable|exists:rdc_communes,id',
            'quartier' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'parent_ids' => 'nullable|array',
            'parent_ids.*' => 'integer',
            'guardian_name' => 'required|string|max:255',
            'guardian_relation' => 'nullable|string|max:255',
            'guardian_phone' => 'required|string|max:50',
            'guardian_email' => 'nullable|email|max:255',
            'class_id' => 'required|exists:school_classes,id',
            'academic_year' => 'nullable|string|max:20',
            'academic_status' => 'nullable|string|max:100',
            'previous_school' => 'nullable|string|max:255',
            'enrollment_date' => 'required|date',
            'allergies' => 'nullable|string',
            'medical_conditions' => 'nullable|string',
            'emergency_contact' => 'nullable|string|max:255',
            'medical_info' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
            'status' => 'sometimes|string|in:active,inactive,suspended',
        ]);

        if (!array_key_exists('is_active', $validated)) {
            $validated['is_active'] = ($validated['status'] ?? 'active') === 'active';
        }

        $this->syncLocationFields($validated);

        $student = Student::create($validated);

        return (new StudentResource($student->load('schoolClass')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $student = Student::with('schoolClass')->findOrFail($id);
        return new StudentResource($student);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'matricule' => 'sometimes|string|unique:students,matricule,' . $id,
            'student_number' => 'sometimes|string|unique:students,student_number,' . $id,
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'date_of_birth' => 'sometimes|date',
            'gender' => 'sometimes|in:M,F',
            'place_of_birth' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:255',
            'blood_group' => 'nullable|string|max:10',
            'photo' => 'nullable|string|max:2048',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'province_id' => 'nullable|exists:rdc_provinces,id',
            'city_id' => 'nullable|exists:rdc_cities,id',
            'commune_id' => 'nullable|exists:rdc_communes,id',
            'quartier' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'parent_ids' => 'nullable|array',
            'parent_ids.*' => 'integer',
            'guardian_name' => 'sometimes|string|max:255',
            'guardian_relation' => 'nullable|string|max:255',
            'guardian_phone' => 'sometimes|string|max:50',
            'guardian_email' => 'nullable|email|max:255',
            'class_id' => 'sometimes|exists:school_classes,id',
            'academic_year' => 'nullable|string|max:20',
            'academic_status' => 'nullable|string|max:100',
            'previous_school' => 'nullable|string|max:255',
            'enrollment_date' => 'sometimes|date',
            'allergies' => 'nullable|string',
            'medical_conditions' => 'nullable|string',
            'emergency_contact' => 'nullable|string|max:255',
            'medical_info' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
            'status' => 'sometimes|string|in:active,inactive,suspended',
        ]);

        if (array_key_exists('status', $validated) && !array_key_exists('is_active', $validated)) {
            $validated['is_active'] = $validated['status'] === 'active';
        }

        if (array_key_exists('is_active', $validated) && !array_key_exists('status', $validated)) {
            $validated['status'] = $validated['is_active'] ? 'active' : 'inactive';
        }

        $this->syncLocationFields($validated);

        $student->update($validated);

        return new StudentResource($student->fresh()->load('schoolClass'));
    }

    protected function syncLocationFields(array &$validated): void
    {
        if (! empty($validated['province_id'])) {
            $validated['province'] = RdcProvince::find($validated['province_id'])?->name ?? $validated['province'] ?? null;
        }
        if (! empty($validated['city_id'])) {
            $validated['city'] = RdcCity::find($validated['city_id'])?->name ?? $validated['city'] ?? null;
        }
        if (! empty($validated['commune_id']) && empty($validated['city_id'])) {
            $commune = RdcCommune::with('city.province')->find($validated['commune_id']);
            if ($commune) {
                $validated['city_id'] = $commune->city_id;
                $validated['city'] = $commune->city?->name;
                $validated['province_id'] = $commune->city?->province_id;
                $validated['province'] = $commune->city?->province?->name;
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $student = Student::findOrFail($id);
        $student->delete();
        return response()->json(null, 204);
    }
}
