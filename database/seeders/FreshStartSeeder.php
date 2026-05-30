<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Environnement de test vierge : rôles RBAC + un seul administrateur.
 *
 * Usage :
 *   php artisan migrate:fresh --seed --seeder=FreshStartSeeder
 */
class FreshStartSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            RdcEducationCatalogSeeder::class,
            RdcGeoSeeder::class,
            FinanceCatalogSeeder::class,
        ]);

        User::query()->delete();

        User::create([
            'first_name' => 'Admin',
            'last_name' => 'SmartSchool',
            'email' => 'admin@smartschool.cd',
            'phone' => '+243 999 000 001',
            'role' => 'admin',
            'department' => 'Direction',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $this->command?->info('✅ Environnement vierge prêt.');
        $this->command?->info('   Compte : admin@smartschool.cd / password');
        $this->command?->info('   Rôles : ' . \App\Models\Role::count() . ' profils RBAC créés.');
        $this->command?->info('   Utilisateurs : 1 (admin uniquement)');
    }
}
