<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class ProductionBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        // Objectif: créer uniquement les données de référence nécessaires pour éviter
        // les dropdowns vides en production (sans seed "démo" élèves/notes/paiements).
        $this->call([
            RolesSeeder::class,
            UsersSeeder::class,
            PersonnelConfigSeeder::class,
            SyncPersonnelFromUsersSeeder::class,
            SchoolSettingsSeeder::class,
            RdcEducationCatalogSeeder::class,
            RdcGeoSeeder::class,
            FinanceCatalogSeeder::class,
            FeeRatesSeeder::class,
            SchoolClassesSeeder::class,
        ]);

        $this->seedSubjects();

        $this->call([
            ClassAssignmentsSeeder::class,
            TeacherDemoStudentsSeeder::class,
        ]);

        $this->command?->info('✅ Production bootstrap terminé (référentiels RH/RDC/finance + matières).');
    }

    private function seedSubjects(): void
    {
        // SetupStatus exige au moins 1 matière. On seed des matières de base de façon idempotente.
        $subjectsData = [
            ['name' => 'Mathématiques', 'code' => 'MATH', 'type' => 'core'],
            ['name' => 'Français', 'code' => 'FRAN', 'type' => 'core'],
            ['name' => 'Histoire-Géographie', 'code' => 'HIST', 'type' => 'core'],
            ['name' => 'Sciences de la Vie et de la Terre', 'code' => 'SVT', 'type' => 'core'],
            ['name' => 'Physique-Chimie', 'code' => 'PHCH', 'type' => 'core'],
            ['name' => 'Anglais', 'code' => 'ANGL', 'type' => 'core'],
            ['name' => 'Éducation Physique et Sportive', 'code' => 'EPS', 'type' => 'core'],
            ['name' => 'Arts Plastiques', 'code' => 'ARTS', 'type' => 'elective'],
            ['name' => 'Musique', 'code' => 'MUSI', 'type' => 'elective'],
        ];

        foreach ($subjectsData as $subject) {
            Subject::updateOrCreate(
                ['code' => $subject['code']],
                array_merge($subject, ['description' => null, 'is_active' => true])
            );
        }
    }
}

