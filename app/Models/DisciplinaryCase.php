<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisciplinaryCase extends Model
{
    protected $fillable = [
        'target_type',
        'student_id',
        'user_id',
        'category',
        'severity',
        'title',
        'description',
        'conduct_note',
        'status',
        'incident_date',
        'reported_by',
    ];

    protected $casts = [
        'incident_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function actions()
    {
        return $this->hasMany(DisciplinaryAction::class);
    }

    public function notes()
    {
        return $this->hasMany(DisciplinaryNote::class);
    }
}
