<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'matricule',
        'student_number',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'place_of_birth',
        'nationality',
        'blood_group',
        'photo',
        'address',
        'city',
        'province',
        'phone',
        'email',
        'parent_ids',
        'guardian_name',
        'guardian_relation',
        'guardian_phone',
        'guardian_email',
        'class_id',
        'academic_year',
        'academic_status',
        'previous_school',
        'enrollment_date',
        'allergies',
        'medical_conditions',
        'emergency_contact',
        'medical_info',
        'is_active',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'enrollment_date' => 'date',
        'parent_ids' => 'array',
        'medical_info' => 'array',
        'is_active' => 'boolean',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}
