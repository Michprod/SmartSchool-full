<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    protected $fillable = [
        'student_id',
        'subject_id',
        'teacher_id',
        'class_id',
        'evaluation_session_id',
        'type',
        'term',
        'academic_year',
        'score',
        'max_score',
        'coefficient',
        'title',
        'comment',
        'date',
        'is_published',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'coefficient' => 'decimal:1',
        'date' => 'date',
        'is_published' => 'boolean',
    ];

    /**
     * Élève évalué
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Matière évaluée
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Professeur qui a mis la note
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Classe concernée
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function evaluationSession()
    {
        return $this->belongsTo(EvaluationSession::class);
    }

    /**
     * Score converti sur 20
     */
    public function getScoreOn20Attribute(): float
    {
        if ($this->max_score == 20) {
            return (float) $this->score;
        }
        return (float) ($this->score * 20 / $this->max_score);
    }

    /**
     * Score pondéré (avec coefficient)
     */
    public function getWeightedScoreAttribute(): float
    {
        return $this->score_on_20 * $this->coefficient;
    }

    /**
     * Score maximum pondéré
     */
    public function getWeightedMaxScoreAttribute(): float
    {
        return 20 * $this->coefficient;
    }

    /**
     * Scope pour un trimestre spécifique
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
     * Scope pour un élève
     */
    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope pour une matière
     */
    public function scopeForSubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    /**
     * Scope pour les évaluations publiées
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope pour les évaluations non publiées
     */
    public function scopeUnpublished($query)
    {
        return $query->where('is_published', false);
    }

    /**
     * Publier l'évaluation
     */
    public function publish(): void
    {
        $this->update(['is_published' => true]);
    }

    /**
     * Masquer l'évaluation
     */
    public function unpublish(): void
    {
        $this->update(['is_published' => false]);
    }
}
