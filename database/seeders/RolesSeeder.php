<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Matrice RBAC canonique SmartSchool (alignée API + SPA).
 *
 * Convention : resource:action  ou  resource:*  ou  *
 * Actions : read, write, delete (+ wildcards par module)
 */
class RolesSeeder extends Seeder
{
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
                'description' => 'Supervision, rapports et lecture transversale',
                'permissions' => [
                    'students:read',
                    'classes:read',
                    'grades:read',
                    'finance:read',
                    'discipline:read',
                    'payments:read',
                    'admissions:read',
                    'communication:read',
                    'events:read',
                    'reports:*',
                ],
            ],
            [
                'name' => 'Enseignant',
                'slug' => 'teacher',
                'description' => 'Classes, élèves et notes',
                'permissions' => [
                    'students:read',
                    'students:write',
                    'classes:*',
                    'grades:*',
                    'discipline:read',
                ],
            ],
            [
                'name' => 'Comptable',
                'slug' => 'accountant',
                'description' => 'Finance et paiements',
                'permissions' => [
                    'finance:*',
                    'payments:*',
                    'students:read',
                    'discipline:read',
                ],
            ],
            [
                'name' => 'Secrétaire',
                'slug' => 'secretary',
                'description' => 'Admissions, élèves et communication',
                'permissions' => [
                    'students:*',
                    'classes:*',
                    'admissions:*',
                    'communication:read',
                    'communication:write',
                    'events:read',
                    'discipline:write',
                ],
            ],
            [
                'name' => 'Gestionnaire inventaire',
                'slug' => 'inventory_manager',
                'description' => 'Stock et paramètres de base',
                'permissions' => [
                    'inventory:*',
                    'settings:read',
                ],
            ],
            [
                'name' => 'Parent',
                'slug' => 'parent',
                'description' => 'Suivi limité (portail futur)',
                'permissions' => [
                    'students:read_own',
                    'payments:read_own',
                    'bulletins:read_own',
                    'messages:read',
                ],
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }

        $this->command?->info('✅ ' . count($roles) . ' rôles RBAC initialisés.');
    }
}
