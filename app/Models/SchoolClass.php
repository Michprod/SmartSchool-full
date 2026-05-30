<?php

namespace App\Models;

use App\Support\ClassNameBuilder;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'level',
        'grade_level_id',
        'study_option_id',
        'section',
        'academic_year',
        'capacity',
        'teacher_id',
        'schedule',
    ];

    protected $casts = [
        'schedule' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (SchoolClass $class) {
            if ($class->grade_level_id) {
                $gradeLevel = $class->gradeLevel ?? GradeLevel::with('educationCycle')->find($class->grade_level_id);
                $studyOption = $class->study_option_id
                    ? ($class->studyOption ?? StudyOption::find($class->study_option_id))
                    : null;

                if ($gradeLevel) {
                    $displayName = ClassNameBuilder::build(
                        $gradeLevel,
                        $studyOption,
                        $class->section ?: 'A'
                    );
                    $class->display_name = $displayName;
                    $class->name = $displayName;
                    $class->level = $gradeLevel->educationCycle?->name ?? $class->level;
                }
            } elseif ($class->display_name && ! $class->name) {
                $class->name = $class->display_name;
            }
        });
    }

    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function studyOption()
    {
        return $this->belongsTo(StudyOption::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function subjectTeachers()
    {
        return $this->belongsToMany(User::class, 'class_subject', 'class_id', 'teacher_id')
            ->withPivot('subject_id', 'coefficient', 'academic_year', 'is_active')
            ->withTimestamps();
    }

    public function activeSubjectTeachers()
    {
        return $this->subjectTeachers()->wherePivot('is_active', true);
    }

    public function teachersBySubject()
    {
        return $this->classSubjects()
            ->where('is_active', true)
            ->with(['subject', 'teacher'])
            ->get()
            ->groupBy(fn ($cs) => $cs->subject?->name ?? 'unknown');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subject', 'class_id', 'subject_id')
            ->withPivot('coefficient', 'hours_per_week', 'teacher_id', 'academic_year', 'is_active')
            ->withTimestamps();
    }

    public function activeSubjects()
    {
        return $this->subjects()->wherePivot('is_active', true);
    }

    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class, 'class_id');
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class, 'class_id');
    }

    public function studentAverages()
    {
        return $this->hasMany(StudentAverage::class, 'class_id');
    }

    public function reportCards()
    {
        return $this->hasMany(ReportCard::class, 'class_id');
    }

    public function getSubjectsWithTeachers(string $academicYear)
    {
        return $this->subjects()
            ->wherePivot('academic_year', $academicYear)
            ->wherePivot('is_active', true)
            ->get()
            ->map(function ($subject) {
                return [
                    'subject' => $subject,
                    'teacher' => User::find($subject->pivot->teacher_id),
                    'coefficient' => $subject->pivot->coefficient,
                    'hours_per_week' => $subject->pivot->hours_per_week,
                ];
            });
    }

    public function getTotalCoefficients(string $academicYear): int
    {
        return $this->classSubjects()
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->sum('coefficient');
    }
}
