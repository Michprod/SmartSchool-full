<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisciplinaryAction extends Model
{
    protected $fillable = [
        'disciplinary_case_id',
        'action_type',
        'reason',
        'action_date',
        'end_date',
        'decided_by',
    ];

    protected $casts = [
        'action_date' => 'date',
        'end_date' => 'date',
    ];

    public function disciplinaryCase()
    {
        return $this->belongsTo(DisciplinaryCase::class);
    }
}
