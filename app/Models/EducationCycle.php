<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EducationCycle extends Model
{
    protected $fillable = ['code', 'name', 'description', 'sort_order'];

    public function gradeLevels(): HasMany
    {
        return $this->hasMany(GradeLevel::class)->orderBy('sort_order');
    }

    public function studyOptions(): HasMany
    {
        return $this->hasMany(StudyOption::class)->orderBy('sort_order');
    }

    public function requiresStudyOption(): bool
    {
        return $this->code === 'humanites';
    }
}
