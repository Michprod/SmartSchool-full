<?php

namespace Database\Seeders;

use App\Models\PersonnelConfigItem;
use App\Models\User;
use App\Services\PersonnelService;
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

        $this->seedPersonnelRef();

        User::query()->delete();
        \App\Models\Personnel::query()->delete();

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

        /** @var PersonnelService $personnelService */
        $personnelService = app(PersonnelService::class);

        $teacher1 = $personnelService->createWithUser([
            'staff_type' => 'teacher',
            'first_name' => 'Jean',
            'last_name' => 'Mukendi',
            'email' => 'jean.mukendi@smartschool.cd',
            'phone' => '+243 999 000 010',
            'password' => 'password',
            'department' => 'Sciences',
            'job_title' => 'Professeur de Mathématiques',
            'workload_hours' => 22,
            'is_active' => true,
        ]);

        $teacher2 = $personnelService->createWithUser([
            'staff_type' => 'teacher',
            'first_name' => 'Claire',
            'last_name' => 'Kabongo',
            'email' => 'claire.kabongo@smartschool.cd',
            'phone' => '+243 999 000 011',
            'password' => 'password',
            'department' => 'Lettres',
            'job_title' => 'Professeur de Français',
            'workload_hours' => 20,
            'is_active' => true,
        ]);

        $gradeLevel = \App\Models\GradeLevel::where('code', 'prim_6')->first();
        $class = \App\Models\SchoolClass::query()->first();

        if (! $class && $gradeLevel) {
            $class = \App\Models\SchoolClass::create([
                'grade_level_id' => $gradeLevel->id,
                'section' => 'A',
                'academic_year' => '2025-2026',
                'capacity' => 35,
                'teacher_id' => $teacher1->user_id,
            ]);
        } elseif ($class) {
            $class->update(['teacher_id' => $teacher1->user_id]);
        }

        if ($class) {
            $math = \App\Models\Subject::firstOrCreate(
                ['code' => 'MATH'],
                ['name' => 'Mathématiques', 'type' => 'core']
            );
            $fr = \App\Models\Subject::firstOrCreate(
                ['code' => 'FR'],
                ['name' => 'Français', 'type' => 'core']
            );

            \App\Models\ClassSubject::firstOrCreate(
                ['class_id' => $class->id, 'subject_id' => $math->id, 'academic_year' => '2025-2026'],
                [
                    'teacher_id' => $teacher1->user_id,
                    'coefficient' => 4,
                    'hours_per_week' => 6,
                    'is_active' => true,
                    'schedule' => ['monday' => [['start' => '08:00', 'end' => '10:00', 'room' => 'Salle 12']]],
                ]
            );

            \App\Models\ClassSubject::firstOrCreate(
                ['class_id' => $class->id, 'subject_id' => $fr->id, 'academic_year' => '2025-2026'],
                [
                    'teacher_id' => $teacher2->user_id,
                    'coefficient' => 3,
                    'hours_per_week' => 4,
                    'is_active' => true,
                    'schedule' => ['tuesday' => [['start' => '10:00', 'end' => '12:00', 'room' => 'Salle 8']]],
                ]
            );
        }

        $this->command?->info('✅ Environnement vierge prêt.');
        $this->command?->info('   Compte : admin@smartschool.cd / password');
        $this->command?->info('   Enseignants démo : jean.mukendi@ / claire.kabongo@ (password)');
        $this->command?->info('   Rôles : ' . \App\Models\Role::count() . ' profils RBAC créés.');
        $this->command?->info('   Personnel : ' . \App\Models\Personnel::count());
        $this->command?->info('   Utilisateurs : ' . User::count());
    }

    private function seedPersonnelRef(): void
    {
        $items = [
            ['type' => 'department', 'label' => 'Sciences'],
            ['type' => 'department', 'label' => 'Lettres'],
            ['type' => 'department', 'label' => 'Direction'],
            ['type' => 'job_grade', 'label' => 'A1'],
            ['type' => 'job_grade', 'label' => 'A2'],
            ['type' => 'contract_type', 'label' => 'CDI'],
            ['type' => 'contract_type', 'label' => 'CDD'],
        ];

        foreach ($items as $item) {
            PersonnelConfigItem::firstOrCreate(
                ['type' => $item['type'], 'label' => $item['label']],
                ['is_active' => true]
            );
        }
    }
}
