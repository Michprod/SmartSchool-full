<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationSession extends Model
{
    protected $fillable = [
        'class_id',
        'subject_id',
        'teacher_id',
        'type',
        'term',
        'academic_year',
        'title',
        'date',
        'max_score',
        'coefficient',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'date' => 'date',
        'max_score' => 'decimal:2',
        'coefficient' => 'decimal:1',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    public function publish(): void
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
        $this->assessments()->update(['is_published' => true]);
    }
}
