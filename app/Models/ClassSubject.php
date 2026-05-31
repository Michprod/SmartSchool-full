<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassSubject extends Model
{
    protected $table = 'class_subject';

    protected $fillable = [
        'class_id',
        'subject_id',
        'teacher_id',
        'coefficient',
        'hours_per_week',
        'schedule',
        'academic_year',
        'is_active',
    ];

    protected $casts = [
        'coefficient' => 'integer',
        'hours_per_week' => 'integer',
        'is_active' => 'boolean',
        'schedule' => 'array',
    ];

    /**
     * Classe
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Matière
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Professeur assigné
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Évaluations pour cette classe/matière
     */
    public function assessments()
    {
        return $this->hasMany(Assessment::class, 'class_id')
            ->where('subject_id', $this->subject_id);
    }

    /**
     * Scope pour une année académique
     */
    public function scopeForAcademicYear($query, string $year)
    {
        return $query->where('academic_year', $year);
    }

    /**
     * Scope pour les associations actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour une classe
     */
    public function scopeForClass($query, int $classId)
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Scope pour une matière
     */
    public function scopeForSubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }
}
