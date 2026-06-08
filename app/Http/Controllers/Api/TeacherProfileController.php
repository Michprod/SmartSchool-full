<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TeacherProfileService;
use App\Services\TeacherWorkloadService;
use App\Services\TimetableService;
use Illuminate\Http\Request;

class TeacherProfileController extends Controller
{
    public function __construct(
        protected TeacherProfileService $profiles,
        protected TeacherWorkloadService $workload,
        protected TimetableService $timetable
    ) {}

    public function index(Request $request)
    {
        if (! $request->user()->hasPermission('teachers:read') && ! $request->user()->hasPermission('users:read')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = User::query()
            ->where('role', 'teacher')
            ->orderBy('last_name');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $year = $request->string('academic_year')->toString() ?: null;

        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function (User $teacher) use ($year) {
            return $this->profiles->build($teacher, $year);
        });

        return response()->json($paginator);
    }

    public function show(Request $request, User $user)
    {
        if ($user->role !== 'teacher') {
            return response()->json(['message' => 'Not a teacher account'], 404);
        }

        if ($request->user()->id !== $user->id
            && ! $request->user()->hasPermission('teachers:read')
            && ! $request->user()->hasPermission('users:read')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $year = $request->string('academic_year')->toString() ?: null;

        return response()->json($this->profiles->build($user, $year));
    }

    public function myProfile(Request $request)
    {
        $user = $request->user();

        if (! $user->hasAnyRole(['teacher', 'admin'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $year = $request->string('academic_year')->toString() ?: null;

        return response()->json($this->profiles->build($user, $year));
    }

    public function workloadSummary(Request $request)
    {
        if (! $request->user()->hasPermission('teachers:read') && ! $request->user()->hasPermission('users:read')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $year = $request->string('academic_year')->toString() ?: null;

        return response()->json([
            'academic_year' => $year,
            'teachers' => $this->workload->allTeachersSummary($year),
        ]);
    }

    public function myTimetable(Request $request)
    {
        $user = $request->user();

        if (! $user->hasAnyRole(['teacher', 'admin'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $year = $request->string('academic_year')->toString() ?: null;

        $assignments = \App\Models\ClassSubject::query()
            ->where('teacher_id', $user->id)
            ->where('is_active', true)
            ->when($year, fn ($q) => $q->where('academic_year', $year))
            ->with(['schoolClass', 'subject', 'teacher'])
            ->get();

        return response()->json([
            'principal_class' => $user->principalClass,
            'slots' => $this->timetable->flattenSlots($assignments),
            'assignments_count' => $assignments->count(),
        ]);
    }

    public function myClasses(Request $request)
    {
        $user = $request->user();

        if (! $user->hasAnyRole(['teacher', 'admin'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $year = $request->string('academic_year')->toString() ?: null;
        $groups = $user->myClassesAssignmentGroups($year);

        $payload = collect($groups)->map(function (array $group) {
            /** @var \App\Models\SchoolClass $class */
            $class = $group['class'];
            $class->loadCount('students');

            return [
                'class' => $class,
                'students_count' => $class->students_count,
                'is_principal' => $group['is_principal'],
                'subjects' => $group['subjects'],
            ];
        })->values();

        return response()->json($payload);
    }
}
