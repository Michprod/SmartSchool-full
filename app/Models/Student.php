<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'matricule',
        'student_number',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'place_of_birth',
        'nationality',
        'blood_group',
        'photo',
        'address',
        'city',
        'province',
        'phone',
        'email',
        'parent_ids',
        'guardian_name',
        'guardian_relation',
        'guardian_phone',
        'guardian_email',
        'class_id',
        'academic_year',
        'academic_status',
        'previous_school',
        'enrollment_date',
        'allergies',
        'medical_conditions',
        'emergency_contact',
        'medical_info',
        'is_active',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'enrollment_date' => 'date',
        'parent_ids' => 'array',
        'medical_info' => 'array',
        'is_active' => 'boolean',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Obtenir le professeur titulaire de la classe de l'élève
     */
    public function classPrincipalTeacher()
    {
        return $this->schoolClass?->teacher;
    }

    /**
     * Obtenir tous les professeurs de la classe de l'élève (titulaire + matières)
     */
    public function allClassTeachers()
    {
        if (!$this->schoolClass) {
            return collect();
        }

        $teachers = collect();

        // Ajouter le professeur titulaire
        if ($this->schoolClass->teacher) {
            $teachers->push([
                'teacher' => $this->schoolClass->teacher,
                'role' => 'titulaire',
                'subject' => null,
            ]);
        }

        // Ajouter les professeurs de matières
        foreach ($this->schoolClass->activeSubjectTeachers as $teacher) {
            $teachers->push([
                'teacher' => $teacher,
                'role' => 'matière',
                'subject' => $teacher->pivot->subject,
            ]);
        }

        return $teachers;
    }

    // ============================================================
    // RELATIONS SYSTÈME DE NOTES
    // ============================================================

    /**
     * Toutes les évaluations de l'élève
     */
    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    /**
     * Moyennes de l'élève par matière
     */
    public function averages()
    {
        return $this->hasMany(StudentAverage::class);
    }

    /**
     * Moyennes pour un trimestre spécifique
     */
    public function averagesForTerm(string $term, string $academicYear)
    {
        return $this->averages()
            ->where('term', $term)
            ->where('academic_year', $academicYear);
    }

    /**
     * Moyenne générale pour un trimestre
     */
    public function generalAverage(string $term, string $academicYear): ?float
    {
        $average = $this->averagesForTerm($term, $academicYear)
            ->whereNotNull('general_average')
            ->first();

        return $average ? (float) $average->general_average : null;
    }

    /**
     * Rang dans la classe pour un trimestre
     */
    public function classRank(string $term, string $academicYear): ?int
    {
        $average = $this->averagesForTerm($term, $academicYear)
            ->whereNotNull('class_rank')
            ->first();

        return $average ? (int) $average->class_rank : null;
    }

    /**
     * Bulletins de l'élève
     */
    public function reportCards()
    {
        return $this->hasMany(ReportCard::class);
    }

    /**
     * Bulletin pour un trimestre spécifique
     */
    public function reportCardForTerm(string $term, string $academicYear): ?ReportCard
    {
        return $this->reportCards()
            ->where('term', $term)
            ->where('academic_year', $academicYear)
            ->first();
    }

    /**
     * Obtenir toutes les notes d'un trimestre groupées par matière
     */
    public function getAssessmentsBySubject(string $term, string $academicYear)
    {
        return $this->assessments()
            ->where('term', $term)
            ->where('academic_year', $academicYear)
            ->with('subject')
            ->get()
            ->groupBy('subject.name');
    }
}
