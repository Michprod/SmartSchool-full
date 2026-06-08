<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\StudentAverage;
use App\Services\AcademicPeriodService;
use App\Services\AcademicProfileService;
use App\Services\ReportCardPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParentAcademicController extends Controller
{
    public function __construct(
        protected AcademicPeriodService $periods,
        protected AcademicProfileService $profiles,
        protected BulletinAccessService $bulletinAccess,
        protected ReportCardPdfService $reportCardPdf
    ) {}

    /**
     * Enfants liés au parent connecté.
     */
    public function children()
    {
        $user = Auth::user();

        if ($user->role !== 'parent' && ! $user->hasPermission('bulletins:read_own')) {
            return response()->json(['message' => 'Réservé aux parents.'], 403);
        }

        $students = Student::query()
            ->where('is_active', true)
            ->with('schoolClass')
            ->get()
            ->filter(fn (Student $s) => $this->bulletinAccess->isParentOfStudent($user, $s))
            ->values()
            ->map(fn (Student $s) => [
                'id' => $s->id,
                'first_name' => $s->first_name,
                'last_name' => $s->last_name,
                'matricule' => $s->matricule,
                'class' => $s->schoolClass?->name,
                'class_id' => $s->class_id,
                'bulletin_blocked' => $this->bulletinAccess->hasBlockingUnpaidTuition($s),
                'period_scheme' => $this->periods->schemeForStudent($s),
            ]);

        return response()->json(['children' => $students]);
    }

    /**
     * Évolution scolaire d'un enfant.
     */
    public function childEvolution(Request $request, int $studentId)
    {
        $student = $this->resolveChild($studentId);
        if ($student instanceof \Illuminate\Http\JsonResponse) {
            return $student;
        }

        $validated = $request->validate([
            'academic_year' => 'required|string',
        ]);

        return response()->json(
            $this->profiles->studentEvolution($student, $validated['academic_year'])
        );
    }

    /**
     * Profil académique d'un enfant (période).
     */
    public function childProfile(Request $request, int $studentId)
    {
        $student = $this->resolveChild($studentId);
        if ($student instanceof \Illuminate\Http\JsonResponse) {
            return $student;
        }

        $validated = $request->validate([
            'term' => 'required|'.$this->periods->periodValidationRule(),
            'academic_year' => 'required|string',
        ]);

        return response()->json(
            $this->profiles->studentAcademicProfile(
                $student,
                $validated['term'],
                $validated['academic_year'],
                true
            )
        );
    }

    /**
     * Bulletin publié d'un enfant.
     */
    public function childReportCard(Request $request, int $studentId)
    {
        $student = $this->resolveChild($studentId);
        if ($student instanceof \Illuminate\Http\JsonResponse) {
            return $student;
        }

        $validated = $request->validate([
            'term' => 'required|'.$this->periods->periodValidationRule(),
            'academic_year' => 'required|string',
        ]);

        $reportCard = ReportCard::query()
            ->where('student_id', $studentId)
            ->where('term', $validated['term'])
            ->where('academic_year', $validated['academic_year'])
            ->first();

        if (! $reportCard) {
            return response()->json(['message' => 'Bulletin non disponible.'], 404);
        }

        $access = $this->bulletinAccess->canViewStudentBulletin(Auth::user(), $student, $reportCard);
        if (! $access['allowed']) {
            return response()->json(['message' => $access['reason']], 403);
        }

        $averages = StudentAverage::query()
            ->where('student_id', $studentId)
            ->where('term', $validated['term'])
            ->where('academic_year', $validated['academic_year'])
            ->with('subject')
            ->get();

        return response()->json([
            'report_card' => $reportCard->load(['student', 'schoolClass']),
            'subject_averages' => $averages,
            'rank_display' => $reportCard->class_rank
                ? "{$reportCard->class_rank}/{$reportCard->total_students}"
                : null,
        ]);
    }

    /**
     * Télécharger le bulletin PDF publié d'un enfant.
     */
    public function downloadChildReportCardPdf(Request $request, int $studentId)
    {
        $student = $this->resolveChild($studentId);
        if ($student instanceof \Illuminate\Http\JsonResponse) {
            return $student;
        }

        $validated = $request->validate([
            'term' => 'required|'.$this->periods->periodValidationRule(),
            'academic_year' => 'required|string',
        ]);

        $reportCard = ReportCard::query()
            ->where('student_id', $studentId)
            ->where('term', $validated['term'])
            ->where('academic_year', $validated['academic_year'])
            ->first();

        if (! $reportCard) {
            return response()->json(['message' => 'Bulletin non disponible.'], 404);
        }

        $access = $this->bulletinAccess->canViewStudentBulletin(Auth::user(), $student, $reportCard);
        if (! $access['allowed']) {
            return response()->json(['message' => $access['reason']], 403);
        }

        $pdfContent = $this->reportCardPdf->resolvePdfContent($reportCard, $student);
        $filename = $this->reportCardPdf->downloadFilename($reportCard, $student);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @return Student|\Illuminate\Http\JsonResponse
     */
    private function resolveChild(int $studentId)
    {
        $user = Auth::user();
        $student = Student::with('schoolClass')->findOrFail($studentId);

        $access = $this->bulletinAccess->canViewStudentAcademicData($user, $student);
        if (! $access['allowed']) {
            return response()->json(['message' => $access['reason']], 403);
        }

        return $student;
    }
}
