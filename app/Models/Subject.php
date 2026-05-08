<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Classes où cette matière est enseignée
     */
    public function schoolClasses()
    {
        return $this->belongsToMany(SchoolClass::class, 'class_subject', 'subject_id', 'class_id')
            ->withPivot('coefficient', 'hours_per_week', 'teacher_id', 'academic_year', 'is_active')
            ->withTimestamps();
    }

    /**
     * Professeurs qui enseignent cette matière
     */
    public function teachers()
    {
        return $this->belongsToMany(User::class, 'class_subject', 'subject_id', 'teacher_id')
            ->withPivot('class_id', 'coefficient', 'academic_year')
            ->withTimestamps();
    }

    /**
     * Toutes les évaluations dans cette matière
     */
    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    /**
     * Moyennes des élèves dans cette matière
     */
    public function studentAverages()
    {
        return $this->hasMany(StudentAverage::class);
    }

    /**
     * Scope pour les matières fondamentales
     */
    public function scopeCore($query)
    {
        return $query->where('type', 'core');
    }

    /**
     * Scope pour les matières optionnelles
     */
    public function scopeElective($query)
    {
        return $query->where('type', 'elective');
    }
}
