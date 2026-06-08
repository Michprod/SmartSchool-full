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
            StudentsSeeder::class,
            GradeSystemSeeder::class,
            PaymentsSeeder::class,
            AdmissionsSeeder::class,
            EventsSeeder::class,
            AnnouncementsSeeder::class,
            InventorySeeder::class,
        ]);
        
        $this->command->info('🎉 Base de données SmartSchool initialisée avec succès !');
    }
}
