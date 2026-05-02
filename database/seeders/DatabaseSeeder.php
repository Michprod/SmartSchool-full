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
            UsersSeeder::class,
            SchoolClassesSeeder::class,
            StudentsSeeder::class,
            PaymentsSeeder::class,
            AdmissionsSeeder::class,
            EventsSeeder::class,
            AnnouncementsSeeder::class,
            InventorySeeder::class,
        ]);
        
        $this->command->info('🎉 Base de données SmartSchool initialisée avec succès !');
    }
}
