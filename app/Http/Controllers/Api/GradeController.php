<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAverage;
use App\Models\ReportCard;
use App\Services\GradeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GradeController extends Controller
{
    protected $calculationService;

    public function __construct(GradeCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    // ============================================================
    // GESTION DES ÉVALUATIONS (NOTES)
    // ============================================================

    /**
     * Liste des évaluations du professeur connecté
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur est professeur ou admin
        if (!$user->hasAnyRole(['teacher', 'admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = Assessment::where('teacher_id', $user->id)
            ->with(['student', 'subject', 'schoolClass']);

        // Filtres
        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->has('term')) {
            $query->where('term', $request->term);
        }
        if ($request->has('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $assessments = $query->orderBy('date', 'desc')->paginate(20);

        return response()->json($assessments);
    }

    /**
     * Créer une nouvelle évaluation
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:school_classes,id',
            'type' => 'required|in:interrogation,devoir,composition,examen,projet,participation',
            'term' => 'required|in:T1,T2,T3',
            'academic_year' => 'required|string',
            'score' => 'required|numeric|min:0',
            'max_score' => 'required|numeric|min:1',
            'coefficient' => 'nullable|numeric|min:0',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string',
            'date' => 'required|date',
        ]);

        // Vérifier que le professeur peut noter cette classe/matière
        if (!$user->canGrade($validated['class_id'], $validated['subject_id'])) {
            return response()->json([
                'error' => 'You are not authorized to grade this class/subject'
            ], 403);
        }

        // Vérifier que l'élève appartient à la classe
        $student = Student::find($validated['student_id']);
        if ($student->class_id != $validated['class_id']) {
            return response()->json([
                'error' => 'Student does not belong to this class'
            ], 400);
        }

        $validated['teacher_id'] = $user->id;
        $validated['is_published'] = false; // Par défaut, non publié

        $assessment = Assessment::create($validated);

        return response()->json([
            'message' => 'Assessment created successfully',
            'data' => $assessment->load(['student', 'subject'])
        ], 201);
    }

    /**
     * Voir une évaluation
     */
    public function show(int $id)
    {
        $user = Auth::user();
        $assessment = Assessment::with(['student', 'subject', 'schoolClass'])->findOrFail($id);

        // Vérifier les droits d'accès
        if ($assessment->teacher_id != $user->id && !$user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($assessment);
    }

    /**
     * Mettre à jour une évaluation
     */
    public function update(Request $request, int $id)
    {
        $user = Auth::user();
        $assessment = Assessment::findOrFail($id);

        // Vérifier les droits
        if ($assessment->teacher_id != $user->id && !$user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'score' => 'sometimes|required|numeric|min:0',
            'max_score' => 'sometimes|required|numeric|min:1',
            'coefficient' => 'sometimes|required|numeric|min:0',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string',
            'date' => 'sometimes|required|date',
            'type' => 'sometimes|required|in:interrogation,devoir,composition,examen,projet,participation',
        ]);

        $assessment->update($validated);

        return response()->json([
            'message' => 'Assessment updated successfully',
            'data' => $assessment->load(['student', 'subject'])
        ]);
    }

    /**
     * Supprimer une évaluation
     */
    public function destroy(int $id)
    {
        $user = Auth::user();
        $assessment = Assessment::findOrFail($id);

        // Vérifier les droits
        if ($assessment->teacher_id != $user->id && !$user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $assessment->delete();

        return response()->json(['message' => 'Assessment deleted successfully']);
    }

    /**
     * Publier une évaluation (rendre visible aux élèves/parents)
     */
    public function publish(int $id)
    {
        $user = Auth::user();
        $assessment = Assessment::findOrFail($id);

        if ($assessment->teacher_id != $user->id && !$user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $assessment->publish();

        return response()->json([
            'message' => 'Assessment published successfully',
            'data' => $assessment
        ]);
    }

    /**
     * Créer des évaluations en masse pour une classe
     */
    public function bulkStore(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'type' => 'required|in:interrogation,devoir,composition,examen,projet,participation',
            'term' => 'required|in:T1,T2,T3',
            'academic_year' => 'required|string',
            'max_score' => 'required|numeric|min:1',
            'coefficient' => 'nullable|numeric|min:0',
            'title' => 'nullable|string|max:255',
            'date' => 'required|date',
            'grades' => 'required|array',
            'grades.*.student_id' => 'required|exists:students,id',
            'grades.*.score' => 'required|numeric|min:0',
            'grades.*.comment' => 'nullable|string',
        ]);

        // Vérifier l'autorisation
        if (!$user->canGrade($validated['class_id'], $validated['subject_id'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $assessments = [];

        DB::transaction(function () use ($validated, $user, &$assessments) {
            foreach ($validated['grades'] as $grade) {
                $assessments[] = Assessment::create([
                    'student_id' => $grade['student_id'],
                    'subject_id' => $validated['subject_id'],
                    'teacher_id' => $user->id,
                    'class_id' => $validated['class_id'],
                    'type' => $validated['type'],
                    'term' => $validated['term'],
                    'academic_year' => $validated['academic_year'],
                    'score' => $grade['score'],
                    'max_score' => $validated['max_score'],
                    'coefficient' => $validated['coefficient'] ?? 1,
                    'title' => $validated['title'] ?? null,
                    'comment' => $grade['comment'] ?? null,
                    'date' => $validated['date'],
                    'is_published' => false,
                ]);
            }
        });

        return response()->json([
            'message' => count($assessments) . ' assessments created successfully',
            'data' => $assessments
        ], 201);
    }

    // ============================================================
    // VUE PROFESSEUR - CLASSES ET ÉLÈVES
    // ============================================================

    /**
     * Obtenir les classes et matières où le professeur enseigne
     */
    public function myClasses()
    {
        $user = Auth::user();

        // If admin, show all classes
        if ($user->hasRole('admin')) {
            $classes = SchoolClass::all();
            $result = [];
            foreach ($classes as $class) {
                $result[] = [
                    'class' => $class,
                    'subjects' => ClassSubject::where('class_id', $class->id)
                        ->where('is_active', true)
                        ->with('subject')
                        ->get()
                        ->map(function ($item) {
                            return [
                                'subject' => $item->subject,
                                'coefficient' => $item->coefficient,
                                'hours_per_week' => $item->hours_per_week,
                                'academic_year' => $item->academic_year,
                            ];
                        }),
                ];
            }
            return response()->json($result);
        }

        $classSubjects = ClassSubject::where('teacher_id', $user->id)
            ->where('is_active', true)
            ->with(['schoolClass', 'subject'])
            ->get()
            ->groupBy('class_id');

        $result = [];
        foreach ($classSubjects as $classId => $items) {
            $class = $items->first()->schoolClass;
            if (!$class) continue;
            
            $result[] = [
                'class' => $class,
                'subjects' => $items->map(function ($item) {
                    return [
                        'subject' => $item->subject,
                        'coefficient' => $item->coefficient,
                        'hours_per_week' => $item->hours_per_week,
                        'academic_year' => $item->academic_year,
                    ];
                }),
            ];
        }

        return response()->json($result);
    }

    /**
     * Obtenir les élèves d'une classe pour notation
     */
    public function classStudents(int $classId)
    {
        $user = Auth::user();
        $class = SchoolClass::findOrFail($classId);

        // Vérifier que le professeur enseigne dans cette classe
        $teachesClass = ClassSubject::where('class_id', $classId)
            ->where('teacher_id', $user->id)
            ->where('is_active', true)
            ->exists();

        if (!$teachesClass && !$user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $students = Student::where('class_id', $classId)
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return response()->json([
            'class' => $class,
            'students' => $students
        ]);
    }

    // ============================================================
    // CALCUL DES MOYENNES
    // ============================================================

    /**
     * Calculer les moyennes pour un élève
     */
    public function calculateStudentAverages(Request $request, int $studentId)
    {
        $user = Auth::user();
        $student = Student::findOrFail($studentId);

        $validated = $request->validate([
            'term' => 'required|in:T1,T2,T3',
            'academic_year' => 'required|string',
        ]);

        // Vérifier que le professeur peut évaluer cet élève
        if (!$user->canGrade($student->class_id, 0) && !$user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $result = $this->calculationService->calculateAndSaveStudentAverages(
            $studentId,
            $validated['term'],
            $validated['academic_year']
        );

        return response()->json([
            'message' => 'Averages calculated successfully',
            'data' => $result
        ]);
    }

    /**
     * Calculer toutes les moyennes d'une classe
     */
    public function calculateClassAverages(Request $request, int $classId)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'term' => 'required|in:T1,T2,T3',
            'academic_year' => 'required|string',
        ]);

        // Vérifier que le professeur enseigne dans cette classe ou est admin/titulaire
        $class = SchoolClass::findOrFail($classId);
        $isAuthorized = $class->teacher_id == $user->id ||
            ClassSubject::where('class_id', $classId)
                ->where('teacher_id', $user->id)
                ->where('is_active', true)
                ->exists() ||
            $user->hasRole('admin');

        if (!$isAuthorized) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $result = $this->calculationService->calculateClassAverages(
            $classId,
            $validated['term'],
            $validated['academic_year']
        );

        return response()->json([
            'message' => 'Class averages calculated successfully',
            'data' => $result
        ]);
    }

    // ============================================================
    // MOYENNES ET RÉSULTATS
    // ============================================================

    /**
     * Obtenir les moyennes d'un élève
     */
    public function studentAverages(Request $request, int $studentId)
    {
        $user = Auth::user();
        $student = Student::findOrFail($studentId);

        $validated = $request->validate([
            'term' => 'required|in:T1,T2,T3',
            'academic_year' => 'required|string',
        ]);

        // Vérifier les droits d'accès
        $isAuthorized = $user->hasRole('admin') ||
            $user->canGrade($student->class_id, 0) ||
            $student->classPrincipalTeacher?->id == $user->id;

        if (!$isAuthorized) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $averages = StudentAverage::where('student_id', $studentId)
            ->where('term', $validated['term'])
            ->where('academic_year', $validated['academic_year'])
            ->with('subject')
            ->get();

        $generalAverage = $averages->first()?->general_average;
        $classRank = $averages->first()?->class_rank;
        $totalStudents = $averages->first()?->total_students;

        return response()->json([
            'student' => $student,
            'term' => $validated['term'],
            'academic_year' => $validated['academic_year'],
            'general_average' => $generalAverage,
            'class_rank' => $classRank,
            'total_students' => $totalStudents,
            'subject_averages' => $averages,
        ]);
    }

    /**
     * Obtenir les moyennes de toute la classe
     */
    public function classAverages(Request $request, int $classId)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'term' => 'required|in:T1,T2,T3',
            'academic_year' => 'required|string',
        ]);

        // Vérifier les droits
        $class = SchoolClass::findOrFail($classId);
        $isAuthorized = $class->teacher_id == $user->id ||
            $user->canGrade($classId, 0) ||
            $user->hasRole('admin');

        if (!$isAuthorized) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $students = Student::where('class_id', $classId)
            ->where('is_active', true)
            ->get();

        $results = [];
        foreach ($students as $student) {
            $averages = StudentAverage::where('student_id', $student->id)
                ->where('term', $validated['term'])
                ->where('academic_year', $validated['academic_year'])
                ->with('subject')
                ->get();

            if ($averages->isNotEmpty()) {
                $results[] = [
                    'student' => $student,
                    'general_average' => $averages->first()->general_average,
                    'class_rank' => $averages->first()->class_rank,
                    'subject_averages' => $averages,
                ];
            }
        }

        // Trier par moyenne générale décroissante
        usort($results, function ($a, $b) {
            return $b['general_average'] <=> $a['general_average'];
        });

        // Statistiques de la classe
        $statistics = $this->calculationService->getClassStatistics(
            $classId,
            $validated['term'],
            $validated['academic_year']
        );

        return response()->json([
            'class' => $class,
            'term' => $validated['term'],
            'academic_year' => $validated['academic_year'],
            'statistics' => $statistics,
            'students' => $results,
        ]);
    }

    // ============================================================
    // BULLETINS
    // ============================================================

    /**
     * Générer un bulletin pour un élève
     */
    public function generateReportCard(Request $request, int $studentId)
    {
        $user = Auth::user();
        $student = Student::findOrFail($studentId);

        $validated = $request->validate([
            'term' => 'required|in:T1,T2,T3',
            'academic_year' => 'required|string',
            'class_council_observation' => 'nullable|string',
            'work_recommendations' => 'nullable|string',
            'behavior_recommendations' => 'nullable|string',
        ]);

        // Vérifier les droits (titulaire ou admin)
        $class = SchoolClass::findOrFail($student->class_id);
        $isAuthorized = $class->teacher_id == $user->id || $user->hasRole('admin');

        if (!$isAuthorized) {
            return response()->json(['error' => 'Unauthorized - Only class principal or admin can generate report cards'], 403);
        }

        // Récupérer les moyennes
        $averages = StudentAverage::where('student_id', $studentId)
            ->where('term', $validated['term'])
            ->where('academic_year', $validated['academic_year'])
            ->get();

        if ($averages->isEmpty()) {
            return response()->json(['error' => 'No averages found. Please calculate averages first.'], 400);
        }

        $generalAverage = $averages->first()->general_average;
        $classRank = $averages->first()->class_rank;
        $totalStudents = $averages->first()->total_students;

        // Compter les matières en échec
        $failedSubjects = $this->calculationService->countFailedSubjects(
            $studentId,
            $validated['term'],
            $validated['academic_year']
        );

        // Déterminer la décision
        $decision = $this->calculationService->determineDecision($generalAverage, $failedSubjects);

        // Créer ou mettre à jour le bulletin
        $reportCard = ReportCard::updateOrCreate(
            [
                'student_id' => $studentId,
                'term' => $validated['term'],
                'academic_year' => $validated['academic_year'],
            ],
            [
                'class_id' => $student->class_id,
                'general_average' => $generalAverage,
                'class_rank' => $classRank,
                'total_students' => $totalStudents,
                'decision' => $decision,
                'class_council_observation' => $validated['class_council_observation'] ?? null,
                'work_recommendations' => $validated['work_recommendations'] ?? null,
                'behavior_recommendations' => $validated['behavior_recommendations'] ?? null,
                'generated_by' => $user->id,
                'generated_at' => now(),
                'is_published' => false,
            ]
        );

        return response()->json([
            'message' => 'Report card generated successfully',
            'data' => $reportCard->load(['student', 'schoolClass'])
        ]);
    }

    /**
     * Voir le bulletin d'un élève
     */
    public function viewReportCard(Request $request, int $studentId)
    {
        $user = Auth::user();
        $student = Student::findOrFail($studentId);

        $validated = $request->validate([
            'term' => 'required|in:T1,T2,T3',
            'academic_year' => 'required|string',
        ]);

        // Vérifier les droits
        $class = SchoolClass::findOrFail($student->class_id);
        $isAuthorized = $user->hasRole('admin') ||
            $class->teacher_id == $user->id ||
            $user->canGrade($student->class_id, 0);

        if (!$isAuthorized) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $reportCard = ReportCard::where('student_id', $studentId)
            ->where('term', $validated['term'])
            ->where('academic_year', $validated['academic_year'])
            ->with(['student', 'schoolClass', 'generatedBy'])
            ->first();

        if (!$reportCard) {
            return response()->json(['error' => 'Report card not found'], 404);
        }

        // Récupérer les détails des moyennes
        $averages = StudentAverage::where('student_id', $studentId)
            ->where('term', $validated['term'])
            ->where('academic_year', $validated['academic_year'])
            ->with('subject')
            ->get();

        return response()->json([
            'report_card' => $reportCard,
            'subject_averages' => $averages,
        ]);
    }
}
