<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PersonnelResource;
use App\Models\ClassSubject;
use App\Models\Personnel;
use App\Services\PersonnelService;
use App\Services\TeacherProfileService;
use App\Services\TeacherWorkloadService;
use App\Services\TimetableService;
use Illuminate\Http\Request;

class PersonnelController extends Controller
{
    public function __construct(
        protected PersonnelService $personnelService,
        protected TeacherProfileService $teacherProfiles,
        protected TeacherWorkloadService $workload,
        protected TimetableService $timetable
    ) {}

    public function index(Request $request)
    {
        $query = Personnel::query()->with('user')->orderBy('last_name');

        if ($request->filled('staff_type')) {
            $query->where('staff_type', $request->staff_type);
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('staff_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('email', 'like', "%{$search}%"));
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        $perPage = min((int) $request->get('per_page', 20), 100);

        return PersonnelResource::collection($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_type' => 'required|in:' . implode(',', Personnel::STAFF_TYPES),
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:50',
            'avatar' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'province_id' => 'nullable|exists:rdc_provinces,id',
            'city_id' => 'nullable|exists:rdc_cities,id',
            'commune_id' => 'nullable|exists:rdc_communes,id',
            'quartier' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:100',
            'job_grade' => 'nullable|string|max:100',
            'workload_hours' => 'nullable|integer|min:0|max:120',
            'hire_date' => 'nullable|date',
            'contract_type' => 'nullable|string|max:64',
            'employment_status' => 'nullable|in:active,suspended,on_leave',
            'bio' => 'nullable|string',
            'notes' => 'nullable|string',
            'staff_number' => 'nullable|string|max:32|unique:personnel,staff_number',
            'is_active' => 'sometimes|boolean',
        ]);

        $personnel = $this->personnelService->createWithUser($validated);

        return (new PersonnelResource($personnel))->response()->setStatusCode(201);
    }

    public function show(Personnel $personnel)
    {
        return new PersonnelResource($personnel->load('user'));
    }

    public function update(Request $request, Personnel $personnel)
    {
        $validated = $request->validate([
            'staff_type' => 'sometimes|in:' . implode(',', Personnel::STAFF_TYPES),
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:50',
            'avatar' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'province_id' => 'nullable|exists:rdc_provinces,id',
            'city_id' => 'nullable|exists:rdc_cities,id',
            'commune_id' => 'nullable|exists:rdc_communes,id',
            'quartier' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:100',
            'job_grade' => 'nullable|string|max:100',
            'workload_hours' => 'nullable|integer|min:0|max:120',
            'hire_date' => 'nullable|date',
            'contract_type' => 'nullable|string|max:64',
            'employment_status' => 'nullable|in:active,suspended,on_leave',
            'bio' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'email' => 'sometimes|email|unique:users,email,' . $personnel->user_id,
        ]);

        if (isset($validated['email']) && $personnel->user) {
            $personnel->user->update(['email' => $validated['email']]);
            unset($validated['email']);
        }

        if (isset($validated['staff_type']) && $personnel->user) {
            $personnel->user->update(['role' => Personnel::STAFF_TYPE_ROLES[$validated['staff_type']] ?? 'secretary']);
        }

        $personnel = $this->personnelService->update($personnel, $validated);

        return new PersonnelResource($personnel);
    }

    public function destroy(Personnel $personnel)
    {
        $personnel = $this->personnelService->deactivate($personnel);

        return new PersonnelResource($personnel);
    }

    public function me(Request $request)
    {
        $personnel = Personnel::where('user_id', $request->user()->id)->with('user')->first();

        if (! $personnel) {
            return response()->json(['message' => 'Aucune fiche personnel liée.'], 404);
        }

        return new PersonnelResource($personnel);
    }

    public function teachingProfile(Request $request, Personnel $personnel)
    {
        if (! $personnel->isTeacher() || ! $personnel->user) {
            return response()->json(['message' => 'Ce membre du personnel n\'est pas enseignant.'], 404);
        }

        $year = $request->string('academic_year')->toString() ?: null;

        return response()->json($this->teacherProfiles->build($personnel->user, $year));
    }

    public function timetable(Request $request, Personnel $personnel)
    {
        if (! $personnel->isTeacher() || ! $personnel->user) {
            return response()->json(['message' => 'Ce membre du personnel n\'est pas enseignant.'], 404);
        }

        $user = $personnel->user;
        $year = $request->string('academic_year')->toString() ?: null;

        $assignments = ClassSubject::query()
            ->where('teacher_id', $user->id)
            ->where('is_active', true)
            ->when($year, fn ($q) => $q->where('academic_year', $year))
            ->with(['schoolClass', 'subject', 'teacher'])
            ->get();

        return response()->json([
            'personnel_id' => $personnel->id,
            'principal_class' => $user->principalClass,
            'slots' => $this->timetable->flattenSlots($assignments),
            'assignments_count' => $assignments->count(),
        ]);
    }

    public function workloadSummary(Request $request)
    {
        $year = $request->string('academic_year')->toString() ?: null;

        return response()->json([
            'academic_year' => $year,
            'teachers' => $this->workload->allTeachersSummary($year),
        ]);
    }
}
