<?php

namespace Database\Seeders;

use App\Models\EducationCycle;
use App\Models\GradeLevel;
use App\Models\StudyOption;
use Illuminate\Database\Seeder;

class RdcEducationCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $cycles = [
            [
                'code' => 'maternel',
                'name' => 'Enseignement Maternel',
                'description' => 'Cycle préscolaire (3 à 5 ans)',
                'sort_order' => 1,
                'levels' => [
                    ['code' => 'mat_ps', 'official_name' => 'Petite Section', 'typical_age' => 3, 'sort_order' => 1],
                    ['code' => 'mat_ms', 'official_name' => 'Moyenne Section', 'typical_age' => 4, 'sort_order' => 2],
                    ['code' => 'mat_gs', 'official_name' => 'Grande Section', 'typical_age' => 5, 'sort_order' => 3],
                ],
            ],
            [
                'code' => 'primaire',
                'name' => 'Enseignement Primaire',
                'description' => '6 années — degrés Élémentaire, Moyen et Terminal',
                'sort_order' => 2,
                'levels' => [
                    ['code' => 'prim_1', 'official_name' => '1ère année Primaire', 'degree_group' => 'elementaire', 'sort_order' => 1],
                    ['code' => 'prim_2', 'official_name' => '2ème année Primaire', 'degree_group' => 'elementaire', 'sort_order' => 2],
                    ['code' => 'prim_3', 'official_name' => '3ème année Primaire', 'degree_group' => 'moyen', 'sort_order' => 3],
                    ['code' => 'prim_4', 'official_name' => '4ème année Primaire', 'degree_group' => 'moyen', 'sort_order' => 4],
                    ['code' => 'prim_5', 'official_name' => '5ème année Primaire', 'degree_group' => 'terminal', 'sort_order' => 5],
                    ['code' => 'prim_6', 'official_name' => '6ème année Primaire', 'degree_group' => 'terminal', 'exam_label' => 'ENAFEP', 'sort_order' => 6],
                ],
            ],
            [
                'code' => 'cteb',
                'name' => "Cycle Terminal de l'Éducation de Base (CTEB)",
                'description' => "7ème et 8ème année Éducation de Base",
                'sort_order' => 3,
                'levels' => [
                    [
                        'code' => 'cteb_7',
                        'official_name' => '7ème année Éducation de Base',
                        'legacy_name' => null,
                        'sort_order' => 1,
                    ],
                    [
                        'code' => 'cteb_8',
                        'official_name' => '8ème année Éducation de Base',
                        'legacy_name' => null,
                        'exam_label' => 'TENASOSP',
                        'sort_order' => 2,
                    ],
                ],
            ],
            [
                'code' => 'humanites',
                'name' => 'Humanités',
                'description' => '4 années avec option de filière',
                'sort_order' => 4,
                'levels' => [
                    ['code' => 'hum_1', 'official_name' => '1ère année des Humanités', 'legacy_name' => null, 'sort_order' => 1],
                    ['code' => 'hum_2', 'official_name' => '2ème année des Humanités', 'legacy_name' => null, 'sort_order' => 2],
                    ['code' => 'hum_3', 'official_name' => '3ème année des Humanités', 'legacy_name' => null, 'sort_order' => 3],
                    ['code' => 'hum_4', 'official_name' => '4ème année des Humanités', 'legacy_name' => null, 'exam_label' => 'EXETAT', 'sort_order' => 4],
                ],
                'options' => [
                    ['code' => 'opt_sci_mp', 'name' => 'Scientifique Math-Physique', 'category' => 'generale', 'sort_order' => 1],
                    ['code' => 'opt_sci_cb', 'name' => 'Scientifique Chimie-Biologie', 'category' => 'generale', 'sort_order' => 2],
                    ['code' => 'opt_lit', 'name' => 'Littéraire Latin-Philo', 'category' => 'generale', 'sort_order' => 3],
                    ['code' => 'opt_com', 'name' => 'Commerciale et Gestion', 'category' => 'technique', 'sort_order' => 4],
                    ['code' => 'opt_ped', 'name' => 'Pédagogie Générale (Rénovée)', 'category' => 'technique', 'sort_order' => 5],
                    ['code' => 'opt_elec', 'name' => 'Électricité', 'category' => 'technique', 'sort_order' => 6],
                    ['code' => 'opt_meca', 'name' => 'Mécanique', 'category' => 'technique', 'sort_order' => 7],
                    ['code' => 'opt_const', 'name' => 'Construction', 'category' => 'technique', 'sort_order' => 8],
                    ['code' => 'opt_nut', 'name' => 'Nutrition', 'category' => 'technique', 'sort_order' => 9],
                ],
            ],
        ];

        foreach ($cycles as $cycleData) {
            $levels = $cycleData['levels'];
            $options = $cycleData['options'] ?? [];
            unset($cycleData['levels'], $cycleData['options']);

            $cycle = EducationCycle::updateOrCreate(
                ['code' => $cycleData['code']],
                $cycleData
            );

            foreach ($levels as $level) {
                GradeLevel::updateOrCreate(
                    ['code' => $level['code']],
                    array_merge($level, ['education_cycle_id' => $cycle->id])
                );
            }

            foreach ($options as $option) {
                StudyOption::updateOrCreate(
                    ['code' => $option['code']],
                    array_merge($option, ['education_cycle_id' => $cycle->id, 'is_active' => true])
                );
            }
        }

        $this->command?->info('✅ Catalogue pédagogique RDC initialisé.');
    }
}
