<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SchoolClassResource;
use App\Http\Resources\StudentResource;
use App\Models\EducationCycle;
use App\Models\GradeLevel;
use App\Models\SchoolClass;
use App\Models\StudyOption;
use Illuminate\Http\Request;

class SchoolClassController extends Controller
{
    public function catalog()
    {
        $cycles = EducationCycle::with(['gradeLevels', 'studyOptions' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($cycle) => [
                'id' => $cycle->id,
                'code' => $cycle->code,
                'name' => $cycle->name,
                'requires_study_option' => $cycle->requiresStudyOption(),
                'grade_levels' => $cycle->gradeLevels->map(fn ($gl) => [
                    'id' => $gl->id,
                    'code' => $gl->code,
                    'official_name' => $gl->official_name,
                    'exam_label' => $gl->exam_label,
                ]),
                'study_options' => $cycle->studyOptions->map(fn ($opt) => [
                    'id' => $opt->id,
                    'code' => $opt->code,
                    'name' => $opt->name,
                    'category' => $opt->category,
                ]),
            ]);

        return response()->json(['cycles' => $cycles]);
    }

    public function index(Request $request)
    {
        $query = SchoolClass::with(['gradeLevel.educationCycle', 'studyOption', 'teacher'])
            ->withCount('students');

        if ($request->filled('cycle')) {
            $query->whereHas('gradeLevel.educationCycle', fn ($q) => $q->where('code', $request->cycle));
        }
        if ($request->filled('grade_level_id')) {
            $query->where('grade_level_id', $request->grade_level_id);
        }
        if ($request->filled('study_option_id')) {
            $query->where('study_option_id', $request->study_option_id);
        }
        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }
        if ($request->filled('section')) {
            $query->where('section', $request->section);
        }

        $perPage = min((int) $request->get('per_page', 50), 100);

        return SchoolClassResource::collection(
            $query->orderBy('display_name')->paginate($perPage)
        );
    }

    public function store(Request $request)
    {
        $validated = $this->validateClass($request);

        $class = SchoolClass::create($validated);

        return (new SchoolClassResource(
            $class->load(['gradeLevel.educationCycle', 'studyOption', 'teacher'])
        ))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $id)
    {
        $relations = ['gradeLevel.educationCycle', 'studyOption', 'teacher'];

        if ($request->boolean('include_subjects') || $request->input('include') === 'subjects') {
            $relations[] = 'classSubjects.subject';
            $relations[] = 'classSubjects.teacher';
        }

        $class = SchoolClass::with($relations)
            ->withCount('students')
            ->findOrFail($id);

        return new SchoolClassResource($class);
    }

    public function students(string $id)
    {
        $class = SchoolClass::findOrFail($id);

        return StudentResource::collection(
            $class->students()->with('schoolClass')->orderBy('last_name')->get()
        );
    }

    public function update(Request $request, string $id)
    {
        $class = SchoolClass::findOrFail($id);
        $validated = $this->validateClass($request, $class);

        $class->update($validated);

        return new SchoolClassResource(
            $class->load(['gradeLevel.educationCycle', 'studyOption', 'teacher'])->loadCount('students')
        );
    }

    public function destroy(string $id)
    {
        $class = SchoolClass::withCount('students')->findOrFail($id);

        if ($class->students_count > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer une classe qui contient des élèves.',
            ], 422);
        }

        $class->delete();

        return response()->json(null, 204);
    }

    protected function validateClass(Request $request, ?SchoolClass $existing = null): array
    {
        $gradeLevelId = $request->input('grade_level_id', $existing?->grade_level_id);
        $gradeLevel = $gradeLevelId ? GradeLevel::with('educationCycle')->find($gradeLevelId) : null;
        $requiresOption = $gradeLevel?->educationCycle?->code === 'humanites';

        $validated = $request->validate([
            'grade_level_id' => 'required|exists:grade_levels,id',
            'study_option_id' => [
                $requiresOption ? 'required' : 'nullable',
                'exists:study_options,id',
            ],
            'section' => 'required|string|max:8',
            'academic_year' => 'required|string|max:16',
            'capacity' => 'required|integer|min:0',
            'teacher_id' => 'nullable|exists:users,id',
            'schedule' => 'nullable|array',
        ]);

        $duplicate = SchoolClass::query()
            ->where('grade_level_id', $validated['grade_level_id'])
            ->where('section', $validated['section'])
            ->where('academic_year', $validated['academic_year'])
            ->when(
                ! empty($validated['study_option_id']),
                fn ($q) => $q->where('study_option_id', $validated['study_option_id']),
                fn ($q) => $q->whereNull('study_option_id')
            )
            ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
            ->exists();

        if ($duplicate) {
            abort(response()->json([
                'message' => 'Une salle existe déjà pour ce niveau, option, section et année scolaire.',
            ], 422));
        }

        if (! $requiresOption) {
            $validated['study_option_id'] = null;
        }

        return $validated;
    }
}
