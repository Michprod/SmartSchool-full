<?php

namespace App\Services;

use App\Models\ReportCard;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentAverage;
use App\Support\SimplePdfDocument;
use Illuminate\Support\Facades\Storage;

class ReportCardPdfService
{
    public function __construct(
        protected AcademicPeriodService $periods
    ) {}

    /**
     * Génère le PDF, l'enregistre et met à jour pdf_path sur le bulletin.
     */
    public function generateAndStore(ReportCard $reportCard, Student $student): string
    {
        $pdfBinary = $this->buildPdf($reportCard, $student);
        $relativePath = $this->storagePath($reportCard, $student);

        Storage::disk('local')->put($relativePath, $pdfBinary);

        $reportCard->update(['pdf_path' => $relativePath]);

        return $relativePath;
    }

    /**
     * Retourne le contenu binaire du PDF (génère si absent).
     */
    public function resolvePdfContent(ReportCard $reportCard, Student $student): string
    {
        $path = $reportCard->pdf_path;

        if ($path && Storage::disk('local')->exists($path)) {
            $existing = Storage::disk('local')->get($path);
            if ($this->isValidPdf($existing)) {
                return $existing;
            }
            Storage::disk('local')->delete($path);
        }

        $relativePath = $this->generateAndStore($reportCard, $student);

        return Storage::disk('local')->get($relativePath);
    }

    public function isValidPdf(string $content): bool
    {
        return str_starts_with($content, '%PDF-') && str_contains($content, '%%EOF');
    }

    public function downloadFilename(ReportCard $reportCard, Student $student): string
    {
        $matricule = $student->matricule ?: $student->student_number ?: ('eleve-'.$student->id);
        $safeMatricule = preg_replace('/[^A-Za-z0-9_-]+/', '-', $matricule) ?: 'eleve';

        return "bulletin_{$safeMatricule}_{$reportCard->term}_{$reportCard->academic_year}.pdf";
    }

    public function buildPdf(ReportCard $reportCard, Student $student): string
    {
        $student->loadMissing('schoolClass');

        $subjectAverages = StudentAverage::query()
            ->where('student_id', $student->id)
            ->where('term', $reportCard->term)
            ->where('academic_year', $reportCard->academic_year)
            ->whereNotNull('subject_id')
            ->with('subject')
            ->orderBy('subject_id')
            ->get();

        $scheme = $this->periods->schemeForStudent($student);
        $termLabel = $this->periods->periodLabel($reportCard->term, $scheme);
        $school = $this->schoolSettings();

        $pdf = new SimplePdfDocument;
        $pdf->addTitle($school['name']);
        $pdf->addSubtitle('BULLETIN SCOLAIRE — '.$termLabel.' — '.$reportCard->academic_year);

        $pdf->addLine('Eleve : '.$student->last_name.' '.$student->first_name);
        $pdf->addLine('Matricule : '.($student->matricule ?: $student->student_number ?: '—'));
        $pdf->addLine('Classe : '.($student->schoolClass?->display_name ?? $student->schoolClass?->name ?? '—'));
        $pdf->addSpacer(6);

        $tableRows = [];
        foreach ($subjectAverages as $avg) {
            $tableRows[] = [
                $avg->subject?->name ?? 'Matiere',
                number_format((float) $avg->average_score, 2, '.', '').'/20',
                $avg->appreciation ?? StudentAverage::getAppreciation((float) $avg->average_score),
            ];
        }

        if ($tableRows !== []) {
            $pdf->addTable(
                ['Matiere', 'Moyenne', 'Appreciation'],
                $tableRows,
                [180, 70, 250]
            );
        }

        $rankDisplay = $reportCard->class_rank && $reportCard->total_students
            ? "{$reportCard->class_rank}/{$reportCard->total_students}"
            : '—';

        $pdf->addLine('Moyenne generale : '.number_format((float) $reportCard->general_average, 2, '.', '').'/20', 12, true);
        $pdf->addLine('Rang : '.$rankDisplay);
        $pdf->addLine('Decision : '.$reportCard->decision_label);

        if ($reportCard->behavior_recommendations) {
            $pdf->addSpacer(6);
            $pdf->addLine('Conduite / comportement :', 11, true);
            $pdf->addLine($reportCard->behavior_recommendations);
        }

        if ($reportCard->work_recommendations) {
            $pdf->addSpacer(4);
            $pdf->addLine('Recommandations travail :', 11, true);
            $pdf->addLine($reportCard->work_recommendations);
        }

        if ($reportCard->class_council_observation) {
            $pdf->addSpacer(4);
            $pdf->addLine('Observations conseil de classe :', 11, true);
            $pdf->addLine($reportCard->class_council_observation);
        }

        $pdf->addSpacer(16);
        $pdf->addLine('Document genere le '.now()->format('d/m/Y'), 9, false);
        if ($school['city']) {
            $pdf->addLine($school['city'].', le '.now()->format('d/m/Y'), 9, false);
        }

        return $pdf->output();
    }

    /**
     * @return array{name: string, city: ?string}
     */
    private function schoolSettings(): array
    {
        $settings = Setting::query()->where('key', 'school_settings')->first();
        $value = is_array($settings?->value) ? $settings->value : [];

        return [
            'name' => $value['schoolName'] ?? 'SmartSchool RDC',
            'city' => $value['city'] ?? null,
        ];
    }

    private function storagePath(ReportCard $reportCard, Student $student): string
    {
        return sprintf(
            'report-cards/%d_%s_%s.pdf',
            $student->id,
            $reportCard->term,
            str_replace('/', '-', $reportCard->academic_year)
        );
    }
}
