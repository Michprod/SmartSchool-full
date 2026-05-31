<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PersonnelResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'staff_number' => $this->staff_number,
            'staff_type' => $this->staff_type,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => trim("{$this->first_name} {$this->last_name}"),
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar
                ? (str_starts_with($this->avatar, 'http') ? $this->avatar : Storage::disk('public')->url($this->avatar))
                : null,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
            'commune_id' => $this->commune_id,
            'quartier' => $this->quartier,
            'department' => $this->department,
            'job_title' => $this->job_title,
            'job_grade' => $this->job_grade,
            'workload_hours' => $this->workload_hours,
            'hire_date' => $this->hire_date?->format('Y-m-d'),
            'contract_type' => $this->contract_type,
            'employment_status' => $this->employment_status,
            'bio' => $this->bio,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'email' => $this->user->email,
                'role' => $this->user->role,
                'is_active' => $this->user->is_active,
                'last_login' => $this->user->last_login?->toIso8601String(),
            ]),
        ];
    }
}
