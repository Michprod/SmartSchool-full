<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Personnel extends Model
{
    protected $table = 'personnel';

    public const STAFF_TYPES = ['teacher', 'secretary', 'accountant', 'director', 'other'];

    public const STAFF_TYPE_ROLES = [
        'teacher' => 'teacher',
        'secretary' => 'secretary',
        'accountant' => 'accountant',
        'director' => 'director',
        'other' => 'secretary',
    ];

    protected $fillable = [
        'user_id', 'staff_number', 'staff_type',
        'first_name', 'last_name', 'phone', 'avatar',
        'birth_date', 'address', 'city', 'province',
        'province_id', 'city_id', 'commune_id', 'quartier',
        'department', 'job_title', 'job_grade', 'workload_hours',
        'hire_date', 'contract_type', 'employment_status',
        'bio', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'hire_date' => 'date',
            'is_active' => 'boolean',
            'workload_hours' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rdcProvince(): BelongsTo
    {
        return $this->belongsTo(RdcProvince::class, 'province_id');
    }

    public function rdcCity(): BelongsTo
    {
        return $this->belongsTo(RdcCity::class, 'city_id');
    }

    public function rdcCommune(): BelongsTo
    {
        return $this->belongsTo(RdcCommune::class, 'commune_id');
    }

    public function roleForStaffType(): string
    {
        return self::STAFF_TYPE_ROLES[$this->staff_type] ?? 'secretary';
    }

    public function isTeacher(): bool
    {
        return $this->staff_type === 'teacher';
    }
}
