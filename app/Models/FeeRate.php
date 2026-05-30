<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeRate extends Model
{
    protected $fillable = [
        'fee_type_id',
        'academic_year',
        'currency',
        'amount',
        'grade_level_id',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }

    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }
}
