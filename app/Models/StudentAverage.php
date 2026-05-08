<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAverage extends Model
{
    protected $fillable = [
        'student_id',
        'subject_id',
        'class_id',
        'term',
        'academic_year',
        'average_score',
        'total_coefficient',
        'assessments_count',
        'general_average',
        'class_rank',
        'total_students',
        'appreciation',
        'teacher_comment',
        'calculated_at',
    ];

    protected $casts = [
        'average_score' => 'decimal:2',
        'general_average' => 'decimal:2',
        'total_coefficient' => 'integer',
        'assessments_count' => 'integer',
        'class_rank' => 'integer',
        'total_students' => 'integer',
        'calculated_at' => 'datetime',
    ];

    /**
     * Élève
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Matière
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Classe
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Scope pour un trimestre
     */
    public function scopeForTerm($query, string $term)
    {
        return $query->where('term', $term);
    }

    /**
     * Scope pour une année académique
     */
    public function scopeForAcademicYear($query, string $year)
    {
        return $query->where('academic_year', $year);
    }

    /**
     * Scope pour une classe
     */
    public function scopeForClass($query, int $classId)
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Scope pour obtenir les moyennes générales uniquement
     */
    public function scopeGeneralAverages($query)
    {
        return $query->whereNotNull('general_average');
    }

    /**
     * Déterminer l'appréciation automatique
     */
    public static function getAppreciation(float $average): string
    {
        return match (true) {
            $average >= 16 => 'Très Bien - Excellent travail',
            $average >= 14 => 'Bien - Bon travail, continuez ainsi',
            $average >= 12 => 'Assez Bien - Travail satisfaisant',
            $average >= 10 => 'Passable - Efforts à poursuivre',
            $average >= 8  => 'Insuffisant - Besoin d\'un soutien accru',
            default => 'Faible - Accompagnement nécessaire',
        };
    }

    /**
     * Mettre à jour l'appréciation
     */
    public function updateAppreciation(): void
    {
        $this->update([
            'appreciation' => self::getAppreciation((float) $this->average_score),
        ]);
    }
}
