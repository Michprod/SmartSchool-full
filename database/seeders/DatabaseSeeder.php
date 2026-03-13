<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $defaultClasses = [
            '1ère Maternelle', '2ème Maternelle', '3ème Maternelle',
            '1ère Primaire', '2ème Primaire', '3ème Primaire', '4ème Primaire', '5ème Primaire', '6ème Primaire',
            '1ère Secondaire', '2ème Secondaire', '3ème Secondaire', '4ème Secondaire', '5ème Secondaire', '6ème Secondaire'
        ];

        foreach ($defaultClasses as $className) {
            \App\Models\SchoolClass::firstOrCreate(
                ['name' => $className],
                [
                    'level' => 'Général',
                    'capacity' => 30,
                ]
            );
        }
    }
}
