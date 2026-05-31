<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'role' => $this->role,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'department' => $this->department,
            'is_active' => $this->is_active,
            'has_professional_profile' => $this->has_professional_profile,
            'workload_hours' => $this->workload_hours,
            'job_grade' => $this->job_grade,
            'job_title' => $this->job_title,
            'bio' => $this->bio,
            'all_permissions' => $this->all_permissions,
        ];
    }
}
