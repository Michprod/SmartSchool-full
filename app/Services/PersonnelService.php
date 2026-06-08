<?php

namespace App\Services;

use App\Models\Personnel;
use App\Models\RdcCity;
use App\Models\RdcProvince;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PersonnelService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createWithUser(array $data): Personnel
    {
        return DB::transaction(function () use ($data) {
            $staffType = $data['staff_type'];
            $role = $data['role'] ?? Personnel::STAFF_TYPE_ROLES[$staffType] ?? 'secretary';

            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                // Le modèle `User` a le cast `password => hashed`, donc on ne hash pas ici.
                'password' => $data['password'],
                'role' => $role,
                'phone' => $data['phone'] ?? null,
                'avatar' => $this->processAvatar($data['avatar'] ?? null),
                'department' => $data['department'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'job_grade' => $data['job_grade'] ?? null,
                'workload_hours' => $data['workload_hours'] ?? null,
                'has_professional_profile' => true,
                'birth_date' => $data['birth_date'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'province' => $data['province'] ?? null,
                'province_id' => $data['province_id'] ?? null,
                'city_id' => $data['city_id'] ?? null,
                'commune_id' => $data['commune_id'] ?? null,
                'quartier' => $data['quartier'] ?? null,
                'bio' => $data['bio'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $personnel = Personnel::create([
                'user_id' => $user->id,
                'staff_number' => $data['staff_number'] ?? $this->generateStaffNumber(),
                'staff_type' => $staffType,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
                'avatar' => $user->avatar,
                'birth_date' => $data['birth_date'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'province' => $data['province'] ?? null,
                'province_id' => $data['province_id'] ?? null,
                'city_id' => $data['city_id'] ?? null,
                'commune_id' => $data['commune_id'] ?? null,
                'quartier' => $data['quartier'] ?? null,
                'department' => $data['department'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'job_grade' => $data['job_grade'] ?? null,
                'workload_hours' => $data['workload_hours'] ?? null,
                'hire_date' => $data['hire_date'] ?? null,
                'contract_type' => $data['contract_type'] ?? null,
                'employment_status' => $data['employment_status'] ?? 'active',
                'bio' => $data['bio'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            return $personnel->load('user');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Personnel $personnel, array $data): Personnel
    {
        return DB::transaction(function () use ($personnel, $data) {
            $data = $this->syncGeoLabels($data);

            if (! empty($data['avatar'])) {
                $data['avatar'] = $this->processAvatar($data['avatar']);
            }

            $personnel->update($data);
            $this->syncUserFromPersonnel($personnel->fresh());

            return $personnel->fresh()->load('user');
        });
    }

    public function deactivate(Personnel $personnel): Personnel
    {
        $personnel->update(['is_active' => false, 'employment_status' => 'suspended']);
        $personnel->user?->update(['is_active' => false]);

        return $personnel->fresh()->load('user');
    }

    public function syncUserFromPersonnel(Personnel $personnel): void
    {
        $user = $personnel->user;
        if (! $user) {
            return;
        }

        $user->update([
            'first_name' => $personnel->first_name,
            'last_name' => $personnel->last_name,
            'phone' => $personnel->phone,
            'avatar' => $personnel->avatar,
            'department' => $personnel->department,
            'job_title' => $personnel->job_title,
            'job_grade' => $personnel->job_grade,
            'workload_hours' => $personnel->workload_hours,
            'has_professional_profile' => true,
            'birth_date' => $personnel->birth_date,
            'address' => $personnel->address,
            'city' => $personnel->city,
            'province' => $personnel->province,
            'province_id' => $personnel->province_id,
            'city_id' => $personnel->city_id,
            'commune_id' => $personnel->commune_id,
            'quartier' => $personnel->quartier,
            'bio' => $personnel->bio,
            'is_active' => $personnel->is_active,
        ]);
    }

    public function generateStaffNumber(): string
    {
        $last = Personnel::query()->orderByDesc('id')->value('staff_number');
        $num = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $num = (int) $m[1] + 1;
        }

        return 'STF-' . str_pad((string) $num, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function syncGeoLabels(array $data): array
    {
        if (! empty($data['province_id'])) {
            $data['province'] = RdcProvince::find($data['province_id'])?->name ?? ($data['province'] ?? null);
        }
        if (! empty($data['city_id'])) {
            $data['city'] = RdcCity::find($data['city_id'])?->name ?? ($data['city'] ?? null);
        }

        return $data;
    }

    private function processAvatar(?string $avatar): ?string
    {
        if (! $avatar || ! preg_match('/^data:image\/(\w+);base64,/', $avatar)) {
            return $avatar;
        }

        $data = substr($avatar, strpos($avatar, ',') + 1);
        $data = base64_decode($data);
        $imageName = 'avatar_' . time() . '_' . uniqid() . '.png';
        Storage::disk('public')->put('avatars/' . $imageName, $data);

        return 'avatars/' . $imageName;
    }
}
