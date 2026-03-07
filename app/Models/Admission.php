<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admission extends Model
{
    protected $fillable = [
        'student_first_name', 'student_last_name', 'student_date_of_birth', 'student_gender',
        'parent_first_name', 'parent_last_name', 'parent_email', 'parent_phone',
        'applied_class', 'status', 'documents', 'submitted_at', 'reviewed_at', 'reviewed_by', 'notes',
    ];

    protected $casts = [
        'student_date_of_birth' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'documents' => 'array',
    ];

    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
}
