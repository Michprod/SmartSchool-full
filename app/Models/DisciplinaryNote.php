<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisciplinaryNote extends Model
{
    protected $fillable = [
        'disciplinary_case_id',
        'note',
        'author_id',
    ];

    public function disciplinaryCase()
    {
        return $this->belongsTo(DisciplinaryCase::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
