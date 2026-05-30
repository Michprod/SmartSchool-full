<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolClassResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name ?? $this->name,
            'level' => $this->level,
            'section' => $this->section,
            'academic_year' => $this->academic_year,
            'capacity' => $this->capacity,
            'teacher_id' => $this->teacher_id,
            'grade_level_id' => $this->grade_level_id,
            'study_option_id' => $this->study_option_id,
            'students_count' => $this->whenCounted('students'),
            'grade_level' => $this->whenLoaded('gradeLevel', fn () => [
                'id' => $this->gradeLevel->id,
                'code' => $this->gradeLevel->code,
                'official_name' => $this->gradeLevel->official_name,
                'education_cycle' => $this->gradeLevel->educationCycle ? [
                    'id' => $this->gradeLevel->educationCycle->id,
                    'code' => $this->gradeLevel->educationCycle->code,
                    'name' => $this->gradeLevel->educationCycle->name,
                ] : null,
            ]),
            'study_option' => $this->whenLoaded('studyOption', fn () => $this->studyOption ? [
                'id' => $this->studyOption->id,
                'code' => $this->studyOption->code,
                'name' => $this->studyOption->name,
            ] : null),
            'teacher' => $this->whenLoaded('teacher', fn () => $this->teacher ? [
                'id' => $this->teacher->id,
                'first_name' => $this->teacher->first_name,
                'last_name' => $this->teacher->last_name,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
