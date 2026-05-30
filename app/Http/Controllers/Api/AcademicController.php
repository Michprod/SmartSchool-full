<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\AcademicPeriodService;
use App\Services\AcademicProfileService;
use App\Services\BulletinAccessService;
use App\Services\GradeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AcademicController extends Controller
{
    public function __construct(
        protected AcademicPeriodService $periods,
        protected AcademicProfileService $profiles,
        protected BulletinAccessService $bulletinAccess,
        protected GradeCalculationService $calculator
    ) {}

    /**
     * Catalogue périodes / types pour une classe ou un élève.
     */
    public function catalog(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'nullable|exists:school_classes,id',
            'student_id' => 'nullable|exists:students,id',
        ]);

        $class = null;
        if (! empty($validated['student_id'])) {
            $student = Student::with('schoolClass.gradeLevel.educationCycle')->findOrFail($validated['student_id']);
            $class = $student->schoolClass;
        } elseif (! empty($validated['class_id'])) {
            $class = SchoolClass::with('gradeLevel.educationCycle')->findOrFail($validated['class_id']);
        }

        $scheme = $this->periods->schemeForClass($class);

        return response()->json([
            'period_scheme' => $scheme,
            'period_scheme_label' => $scheme === AcademicPeriodService::SCHEME_SEMESTRE ? 'Semestre' : 'Trimestre',
            'periods' => $this->periods->periodsForScheme($scheme),
            'assessment_types' => collect($this->periods->assessmentTypeLabels())
                ->map(fn ($label, $code) => ['code' => $code, 'label' => $label])
                ->values(),
        ]);
    }

    /**
     * Évolution scolaire multi-périodes.
     */
    public function studentEvolution(Request $request, int $studentId)
    {
        $student = Student::with('schoolClass')->findOrFail($studentId);
        $access = $this->bulletinAccess->canViewStudentAcademicData(Auth::user(), $student);
        if (! $access['allowed']) {
            return response()->json(['message' => $access['reason']], 403);
        }

        $validated = $request->validate([
            'academic_year' => 'required|string',
        ]);

        return response()->json(
            $this->profiles->studentEvolution($student, $validated['academic_year'])
        );
    }

    /**
     * Profil académique détaillé (notes par matière / type).
     */
    public function studentProfile(Request $request, int $studentId)
    {
        $student = Student::with('schoolClass')->findOrFail($studentId);
        $user = Auth::user();
        $access = $this->bulletinAccess->canViewStudentAcademicData($user, $student);
        if (! $access['allowed']) {
            return response()->json(['message' => $access['reason']], 403);
        }

        $validated = $request->validate([
            'term' => 'required|'.$this->periods->periodValidationRule(),
            'academic_year' => 'required|string',
        ]);

        if (! $this->periods->isValidPeriodForStudent($student, $validated['term'])) {
            return response()->json([
                'message' => 'Période invalide pour le cycle scolaire de cet élève.',
            ], 422);
        }

        $parentsView = $user->role === 'parent';

        return response()->json(
            $this->profiles->studentAcademicProfile(
                $student,
                $validated['term'],
                $validated['academic_year'],
                $parentsView
            )
        );
    }

    /**
     * Bulletin de classe (classement complet).
     */
    public function classBulletin(Request $request, int $classId)
    {
        $user = Auth::user();
        $class = SchoolClass::findOrFail($classId);

        $isAuthorized = $class->teacher_id == $user->id
            || $user->canGrade($classId, 0)
            || $user->hasRole('admin')
            || $user->hasPermission('grades:read');

        if (! $isAuthorized) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'term' => 'required|'.$this->periods->periodValidationRule(),
            'academic_year' => 'required|string',
        ]);

        $scheme = $this->periods->schemeForClass($class);
        if (! in_array($validated['term'], $this->periods->validPeriodCodes($scheme), true)) {
            return response()->json([
                'message' => 'Période invalide pour le cycle de cette classe.',
            ], 422);
        }

        $data = $this->profiles->classBulletin(
            $classId,
            $validated['term'],
            $validated['academic_year'],
            $this->calculator
        );

        $data['class'] = $class;
        $data['term_label'] = $this->periods->periodLabel($validated['term'], $scheme);
        $data['period_scheme'] = $scheme;

        return response()->json($data);
    }
}
