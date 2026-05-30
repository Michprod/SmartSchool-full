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
            'all_permissions' => $this->all_permissions,
        ];
    }
}
