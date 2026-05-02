<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Administrateur',
                'slug' => 'admin',
                'description' => 'Accès complet au système',
                'permissions' => ['*'],
            ],
            [
                'name' => 'Directeur',
                'slug' => 'director',
                'description' => 'Supervision et rapports',
                'permissions' => ['students:read', 'teachers:read', 'reports:*', 'finance:read'],
            ],
            [
                'name' => 'Enseignant',
                'slug' => 'teacher',
                'description' => 'Gestion des classes et élèves',
                'permissions' => ['students:read', 'students:write', 'classes:*', 'grades:*'],
            ],
            [
                'name' => 'Comptable',
                'slug' => 'accountant',
                'description' => 'Gestion financière',
                'permissions' => ['finance:*', 'payments:*', 'students:read'],
            ],
            [
                'name' => 'Secrétaire',
                'slug' => 'secretary',
                'description' => 'Gestion administrative',
                'permissions' => ['students:*', 'admissions:*', 'communication:write'],
            ],
            [
                'name' => 'Parent',
                'slug' => 'parent',
                'description' => 'Suivi des enfants',
                'permissions' => ['students:read_own', 'payments:read_own', 'messages:read'],
            ],
        ];

        foreach ($roles as $role) {
            \App\Models\Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
