<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConductGrade extends Model
{
    protected $fillable = [
        'student_id',
        'class_id',
        'term',
        'academic_year',
        'conduct_score',
        'appreciation',
        'recorded_by',
    ];

    protected $casts = [
        'conduct_score' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
