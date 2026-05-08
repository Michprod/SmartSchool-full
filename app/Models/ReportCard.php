<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportCard extends Model
{
    protected $fillable = [
        'student_id',
        'class_id',
        'term',
        'academic_year',
        'general_average',
        'class_rank',
        'total_students',
        'decision',
        'class_council_observation',
        'work_recommendations',
        'behavior_recommendations',
        'pdf_path',
        'is_published',
        'published_at',
        'generated_by',
        'generated_at',
        'validated_by',
        'validated_at',
        'is_validated',
    ];

    protected $casts = [
        'general_average' => 'decimal:2',
        'class_rank' => 'integer',
        'total_students' => 'integer',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'generated_at' => 'datetime',
        'is_validated' => 'boolean',
        'validated_at' => 'datetime',
    ];

    /**
     * Élève
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Classe
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Utilisateur qui a généré le bulletin
     */
    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Utilisateur qui a validé le bulletin
     */
    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Moyennes détaillées de l'élève pour ce trimestre
     */
    public function studentAverages()
    {
        return $this->hasMany(StudentAverage::class, 'student_id', 'student_id')
            ->where('term', $this->term)
            ->where('academic_year', $this->academic_year);
    }

    /**
     * Publier le bulletin
     */
    public function publish(): void
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    /**
     * Valider le bulletin
     */
    public function validate(int $validatorId): void
    {
        $this->update([
            'is_validated' => true,
            'validated_by' => $validatorId,
            'validated_at' => now(),
        ]);
    }

    /**
     * Scope pour les bulletins publiés
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope pour les bulletins validés
     */
    public function scopeValidated($query)
    {
        return $query->where('is_validated', true);
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
     * Vérifier si l'élève passe en classe supérieure
     */
    public function isPassing(): bool
    {
        return $this->general_average >= 10 && in_array($this->decision, ['pass', 'conditional_pass']);
    }

    /**
     * Obtenir la décision sous forme de texte
     */
    public function getDecisionLabelAttribute(): string
    {
        return match ($this->decision) {
            'pass' => 'Passe en classe supérieure',
            'conditional_pass' => 'Passe avec conditions',
            'fail' => 'Redouble',
            'exclude' => 'Exclusion',
            default => 'En attente',
        };
    }
}
