<?php

namespace Database\Seeders;

use App\Models\Personnel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncPersonnelFromUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('personnel')) {
            return;
        }

        $staffRoles = ['teacher', 'secretary', 'accountant', 'director'];

        $max = DB::table('personnel')->selectRaw("max(cast(substr(staff_number, 5) as unsigned)) as max_counter")->value('max_counter');
        $counter = $max ? ((int) $max + 1) : 1;

        $users = DB::table('users')
            ->where(function ($q) use ($staffRoles) {
                $q->whereIn('role', $staffRoles)
                    ->orWhere('has_professional_profile', true);
            })
            ->orderBy('id')
            ->get();

        foreach ($users as $user) {
            if (DB::table('personnel')->where('user_id', $user->id)->exists()) {
                continue;
            }

            $staffType = in_array($user->role, $staffRoles, true) ? $user->role : 'other';

            DB::table('personnel')->insert([
                'user_id' => $user->id,
                'staff_number' => 'STF-' . str_pad((string) $counter++, 5, '0', STR_PAD_LEFT),
                'staff_type' => $staffType,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'birth_date' => $user->birth_date,
                'address' => $user->address,
                'city' => $user->city,
                'province' => $user->province,
                'province_id' => $user->province_id ?? null,
                'city_id' => $user->city_id ?? null,
                'commune_id' => $user->commune_id ?? null,
                'quartier' => $user->quartier ?? null,
                'department' => $user->department,
                'job_title' => $user->job_title,
                'job_grade' => $user->job_grade,
                'workload_hours' => $user->workload_hours,
                'bio' => $user->bio,
                'employment_status' => $user->is_active ? 'active' : 'suspended',
                'is_active' => (bool) $user->is_active,
                'created_at' => $user->created_at ?? now(),
                'updated_at' => $user->updated_at ?? now(),
            ]);
        }
    }
}

