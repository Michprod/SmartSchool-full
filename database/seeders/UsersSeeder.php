<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // Admin
            [
                'first_name' => 'Admin',
                'last_name'  => 'Principal',
                'email'      => 'admin@smartschool.cd',
                'phone'      => '+243 999 000 001',
                'role'       => 'admin',
                'department' => 'Direction',
                'is_active'  => true,
                'password'   => Hash::make('password'),
            ],
            // Directeur
            [
                'first_name' => 'Jean-Baptiste',
                'last_name'  => 'Mukendi',
                'email'      => 'directeur@smartschool.cd',
                'phone'      => '+243 991 234 567',
                'role'       => 'director',
                'department' => 'Direction',
                'is_active'  => true,
                'password'   => Hash::make('password'),
            ],
            // Comptable
            [
                'first_name' => 'Marie-Claire',
                'last_name'  => 'Kabila',
                'email'      => 'comptable@smartschool.cd',
                'phone'      => '+243 991 234 568',
                'role'       => 'accountant',
                'department' => 'Finances',
                'is_active'  => true,
                'password'   => Hash::make('password'),
            ],
            // Secrétaire
            [
                'first_name' => 'Grace',
                'last_name'  => 'Tshisekedi',
                'email'      => 'secretaire@smartschool.cd',
                'phone'      => '+243 991 234 569',
                'role'       => 'secretary',
                'department' => 'Administration',
                'is_active'  => true,
                'password'   => Hash::make('password'),
            ],
            // Enseignants
            [
                'first_name' => 'Prof. Emmanuel',
                'last_name'  => 'Kabongo',
                'email'      => 'prof.kabongo@smartschool.cd',
                'phone'      => '+243 991 234 570',
                'role'       => 'teacher',
                'department' => 'Mathématiques',
                'is_active'  => true,
                'password'   => Hash::make('password'),
            ],
            [
                'first_name' => 'Prof. Yvonne',
                'last_name'  => 'Mwamba',
                'email'      => 'prof.mwamba@smartschool.cd',
                'phone'      => '+243 991 234 571',
                'role'       => 'teacher',
                'department' => 'Français',
                'is_active'  => true,
                'password'   => Hash::make('password'),
            ],
            [
                'first_name' => 'Prof. Patrick',
                'last_name'  => 'Lumumba',
                'email'      => 'prof.lumumba@smartschool.cd',
                'phone'      => '+243 991 234 572',
                'role'       => 'teacher',
                'department' => 'Sciences',
                'is_active'  => true,
                'password'   => Hash::make('password'),
            ],
            [
                'first_name' => 'Prof. Cécile',
                'last_name'  => 'Kasongo',
                'email'      => 'prof.kasongo@smartschool.cd',
                'phone'      => '+243 991 234 573',
                'role'       => 'teacher',
                'department' => 'Anglais',
                'is_active'  => true,
                'password'   => Hash::make('password'),
            ],
            [
                'first_name' => 'Prof. Didier',
                'last_name'  => 'Ngalula',
                'email'      => 'prof.ngalula@smartschool.cd',
                'phone'      => '+243 991 234 574',
                'role'       => 'teacher',
                'department' => 'Histoire-Géo',
                'is_active'  => true,
                'password'   => Hash::make('password'),
            ],
            // Parents
            [
                'first_name' => 'Robert',
                'last_name'  => 'Ilunga',
                'email'      => 'parent.ilunga@gmail.com',
                'phone'      => '+243 991 234 580',
                'role'       => 'parent',
                'department' => null,
                'is_active'  => true,
                'password'   => Hash::make('password'),
            ],
            [
                'first_name' => 'Chantal',
                'last_name'  => 'Banza',
                'email'      => 'parent.banza@gmail.com',
                'phone'      => '+243 991 234 581',
                'role'       => 'parent',
                'department' => null,
                'is_active'  => true,
                'password'   => Hash::make('password'),
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $this->command->info('✅ ' . count($users) . ' utilisateurs créés.');
    }
}
