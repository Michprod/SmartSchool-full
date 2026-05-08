<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    protected $fillable = [
        'name',
        'level',
        'capacity',
        'teacher_id',
        'schedule',
    ];

    protected $casts = [
        'schedule' => 'array',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    /**
     * Professeurs qui enseignent dans cette classe (relation many-to-many via table pivot)
     * Inclut les matières enseignées, l'année académique, etc.
     */
    public function subjectTeachers()
    {
        return $this->belongsToMany(User::class, 'class_teacher', 'class_id', 'teacher_id')
            ->withPivot('subject', 'academic_year', 'schedule', 'is_active')
            ->withTimestamps();
    }

    /**
     * Professeurs actifs pour cette classe
     */
    public function activeSubjectTeachers()
    {
        return $this->subjectTeachers()->wherePivot('is_active', true);
    }

    /**
     * Obtenir les professeurs groupés par matière
     */
    public function teachersBySubject()
    {
        return $this->subjectTeachers()
            ->wherePivot('is_active', true)
            ->get()
            ->groupBy('pivot.subject');
    }

    // ============================================================
    // RELATIONS SYSTÈME DE NOTES
    // ============================================================

    /**
     * Matières enseignées dans cette classe avec coefficient
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subject', 'class_id', 'subject_id')
            ->withPivot('coefficient', 'hours_per_week', 'teacher_id', 'academic_year', 'is_active')
            ->withTimestamps();
    }

    /**
     * Matières actives pour cette classe
     */
    public function activeSubjects()
    {
        return $this->subjects()->wherePivot('is_active', true);
    }

    /**
     * Association classe-matière avec détails pivot
     */
    public function classSubjects()
    {
        return $this->hasMany(\App\Models\ClassSubject::class, 'class_id');
    }

    /**
     * Toutes les évaluations de cette classe
     */
    public function assessments()
    {
        return $this->hasMany(Assessment::class, 'class_id');
    }

    /**
     * Moyennes des élèves de cette classe
     */
    public function studentAverages()
    {
        return $this->hasMany(StudentAverage::class, 'class_id');
    }

    /**
     * Bulletins de cette classe
     */
    public function reportCards()
    {
        return $this->hasMany(ReportCard::class, 'class_id');
    }

    /**
     * Obtenir les matières avec leurs professeurs pour une année académique
     */
    public function getSubjectsWithTeachers(string $academicYear)
    {
        return $this->subjects()
            ->wherePivot('academic_year', $academicYear)
            ->wherePivot('is_active', true)
            ->get()
            ->map(function ($subject) {
                return [
                    'subject' => $subject,
                    'teacher' => User::find($subject->pivot->teacher_id),
                    'coefficient' => $subject->pivot->coefficient,
                    'hours_per_week' => $subject->pivot->hours_per_week,
                ];
            });
    }

    /**
     * Calculer le total des coefficients pour cette classe
     */
    public function getTotalCoefficients(string $academicYear): int
    {
        return $this->classSubjects()
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->sum('coefficient');
    }
}
