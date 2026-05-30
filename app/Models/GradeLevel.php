<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeLevel extends Model
{
    protected $fillable = [
        'education_cycle_id',
        'code',
        'official_name',
        'legacy_name',
        'degree_group',
        'exam_label',
        'typical_age',
        'sort_order',
    ];

    public function educationCycle(): BelongsTo
    {
        return $this->belongsTo(EducationCycle::class);
    }

    public function schoolClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }
}
