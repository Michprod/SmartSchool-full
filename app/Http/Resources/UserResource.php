<?php

namespace App\Http\Resources;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = Role::where('slug', $this->role)->first();

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'role' => $this->role,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar
                ? (str_starts_with($this->avatar, 'http') ? $this->avatar : Storage::disk('public')->url($this->avatar))
                : null,
            'department' => $this->department,
            'is_active' => $this->is_active,
            'has_professional_profile' => $this->has_professional_profile,
            'workload_hours' => $this->workload_hours,
            'job_grade' => $this->job_grade,
            'job_title' => $this->job_title,
            'bio' => $this->bio,
            'birth_date' => $this->birth_date?->format('Y-m-d') ?? $this->birth_date,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
            'commune_id' => $this->commune_id,
            'quartier' => $this->quartier,
            'last_login' => $this->last_login?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'all_permissions' => $this->all_permissions,
            'role_info' => $role ? [
                'slug' => $role->slug,
                'name' => $role->name,
                'description' => $role->description,
            ] : null,
        ];
    }
}
