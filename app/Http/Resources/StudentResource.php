<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'matricule' => $this->matricule,
            'student_number' => $this->student_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'date_of_birth' => optional($this->date_of_birth)->toDateString(),
            'gender' => $this->gender,
            'place_of_birth' => $this->place_of_birth,
            'nationality' => $this->nationality,
            'blood_group' => $this->blood_group,
            'photo' => $this->photo,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
            'commune_id' => $this->commune_id,
            'quartier' => $this->quartier,
            'phone' => $this->phone,
            'email' => $this->email,
            'parent_ids' => $this->parent_ids ?? [],
            'guardian_name' => $this->guardian_name,
            'guardian_relation' => $this->guardian_relation,
            'guardian_phone' => $this->guardian_phone,
            'guardian_email' => $this->guardian_email,
            'class_id' => $this->class_id,
            'academic_year' => $this->academic_year,
            'academic_status' => $this->academic_status,
            'previous_school' => $this->previous_school,
            'enrollment_date' => optional($this->enrollment_date)->toDateString(),
            'allergies' => $this->allergies,
            'medical_conditions' => $this->medical_conditions,
            'emergency_contact' => $this->emergency_contact,
            'medical_info' => $this->medical_info,
            'is_active' => (bool) $this->is_active,
            'status' => $this->status,
            'school_class' => $this->whenLoaded('schoolClass', function () {
                return [
                    'id' => $this->schoolClass?->id,
                    'name' => $this->schoolClass?->name,
                    'display_name' => $this->schoolClass?->display_name ?? $this->schoolClass?->name,
                    'level' => $this->schoolClass?->level,
                    'section' => $this->schoolClass?->section,
                    'academic_year' => $this->schoolClass?->academic_year,
                    'teacher_id' => $this->schoolClass?->teacher_id,
                ];
            }),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
