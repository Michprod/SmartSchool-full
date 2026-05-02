<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Database\Seeder;

class SchoolClassesSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer les enseignants créés
        $teachers = User::where('role', 'teacher')->get();

        $classes = [
            // Maternelle
            ['name' => '1ère Maternelle', 'level' => 'Maternelle', 'capacity' => 25],
            ['name' => '2ème Maternelle', 'level' => 'Maternelle', 'capacity' => 25],
            ['name' => '3ème Maternelle', 'level' => 'Maternelle', 'capacity' => 25],

            // Primaire
            ['name' => '1ère Primaire', 'level' => 'Primaire', 'capacity' => 35],
            ['name' => '2ème Primaire', 'level' => 'Primaire', 'capacity' => 35],
            ['name' => '3ème Primaire', 'level' => 'Primaire', 'capacity' => 35],
            ['name' => '4ème Primaire', 'level' => 'Primaire', 'capacity' => 35],
            ['name' => '5ème Primaire', 'level' => 'Primaire', 'capacity' => 35],
            ['name' => '6ème Primaire', 'level' => 'Primaire', 'capacity' => 35],

            // Éducation de Base (Cycle d'Orientation)
            ['name' => '7ème Éducation de Base', 'level' => 'Éducation de Base', 'capacity' => 40],
            ['name' => '8ème Éducation de Base', 'level' => 'Éducation de Base', 'capacity' => 40],

            // Humanités
            ['name' => '1ère Humanités', 'level' => 'Humanités', 'capacity' => 40],
            ['name' => '2ème Humanités', 'level' => 'Humanités', 'capacity' => 40],
            ['name' => '3ème Humanités', 'level' => 'Humanités', 'capacity' => 40],
            ['name' => '4ème Humanités', 'level' => 'Humanités', 'capacity' => 40],
        ];

        foreach ($classes as $i => $classData) {
            SchoolClass::updateOrCreate(
                ['name' => $classData['name']],
                [
                    'level'      => $classData['level'],
                    'capacity'   => $classData['capacity'],
                    'teacher_id' => $teachers->isNotEmpty() ? $teachers->get($i % $teachers->count())?->id : null,
                ]
            );
        }

        $this->command->info('✅ ' . count($classes) . ' classes créées.');
    }
}
