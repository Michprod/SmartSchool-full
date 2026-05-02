<?php

namespace Database\Seeders;

use App\Models\Admission;
use Illuminate\Database\Seeder;

class AdmissionsSeeder extends Seeder
{
    public function run(): void
    {
        $admissions = [
            [
                'student_first_name'   => 'Espoir',
                'student_last_name'    => 'Mulamba',
                'student_date_of_birth'=> '2018-03-12',
                'student_gender'       => 'M',
                'parent_first_name'    => 'Théophile',
                'parent_last_name'     => 'Mulamba',
                'parent_email'         => 'theo.mulamba@gmail.com',
                'parent_phone'         => '+243 810 101 001',
                'applied_class'        => '1ère Primaire',
                'status'               => 'submitted',
                'notes'                => null,
            ],
            [
                'student_first_name'   => 'Joanna',
                'student_last_name'    => 'Kizito',
                'student_date_of_birth'=> '2017-07-08',
                'student_gender'       => 'F',
                'parent_first_name'    => 'Sylvie',
                'parent_last_name'     => 'Kizito',
                'parent_email'         => 'sylvie.kizito@gmail.com',
                'parent_phone'         => '+243 810 101 002',
                'applied_class'        => '2ème Primaire',
                'status'               => 'under_review',
                'notes'                => 'Documents en cours de vérification. Acte de naissance demandé.',
            ],
            [
                'student_first_name'   => 'Emmanuel',
                'student_last_name'    => 'Nsenga',
                'student_date_of_birth'=> '2012-01-25',
                'student_gender'       => 'M',
                'parent_first_name'    => 'Pascal',
                'parent_last_name'     => 'Nsenga',
                'parent_email'         => 'pascal.nsenga@outlook.com',
                'parent_phone'         => '+243 890 101 003',
                'applied_class'        => '7ème Éducation de Base',
                'status'               => 'accepted',
                'notes'                => 'Dossier complet. Résultats CM2 excellents. Candidature acceptée.',
            ],
            [
                'student_first_name'   => 'Chloé',
                'student_last_name'    => 'Tshilolo',
                'student_date_of_birth'=> '2011-09-14',
                'student_gender'       => 'F',
                'parent_first_name'    => 'Bernard',
                'parent_last_name'     => 'Tshilolo',
                'parent_email'         => 'b.tshilolo@gmail.com',
                'parent_phone'         => '+243 810 101 004',
                'applied_class'        => '8ème Éducation de Base',
                'status'               => 'rejected',
                'notes'                => 'Classe complète pour cette année. Dossier réexaminé l\'année prochaine.',
            ],
            [
                'student_first_name'   => 'Michael',
                'student_last_name'    => 'Kapinga',
                'student_date_of_birth'=> '2020-05-20',
                'student_gender'       => 'M',
                'parent_first_name'    => 'Henriette',
                'parent_last_name'     => 'Kapinga',
                'parent_email'         => 'henriette.kapinga@gmail.com',
                'parent_phone'         => '+243 820 101 005',
                'applied_class'        => '2ème Maternelle',
                'status'               => 'submitted',
                'notes'                => null,
            ],
            [
                'student_first_name'   => 'Amandine',
                'student_last_name'    => 'Bunduki',
                'student_date_of_birth'=> '2009-11-03',
                'student_gender'       => 'F',
                'parent_first_name'    => 'Denis',
                'parent_last_name'     => 'Bunduki',
                'parent_email'         => 'denis.bunduki@yahoo.fr',
                'parent_phone'         => '+243 850 101 006',
                'applied_class'        => '2ème Humanités',
                'status'               => 'under_review',
                'notes'                => 'En attente des bulletins de 3ème Secondaire.',
            ],
            [
                'student_first_name'   => 'Christophe',
                'student_last_name'    => 'Kabeya',
                'student_date_of_birth'=> '2013-06-15',
                'student_gender'       => 'M',
                'parent_first_name'    => 'Antoinette',
                'parent_last_name'     => 'Kabeya',
                'parent_email'         => 'a.kabeya@gmail.com',
                'parent_phone'         => '+243 820 101 007',
                'applied_class'        => '6ème Primaire',
                'status'               => 'accepted',
                'notes'                => 'Test de niveau passé avec succès. Inscription validée.',
            ],
            [
                'student_first_name'   => 'Stéphanie',
                'student_last_name'    => 'Luzolo',
                'student_date_of_birth'=> '2021-02-28',
                'student_gender'       => 'F',
                'parent_first_name'    => 'Martin',
                'parent_last_name'     => 'Luzolo',
                'parent_email'         => 'martin.luzolo@gmail.com',
                'parent_phone'         => '+243 810 101 008',
                'applied_class'        => '1ère Maternelle',
                'status'               => 'submitted',
                'notes'                => null,
            ],
        ];

        foreach ($admissions as $data) {
            Admission::create(array_merge($data, [
                'documents'    => json_encode([]),
                'submitted_at' => now()->subDays(rand(1, 30)),
                'reviewed_at'  => in_array($data['status'], ['accepted', 'rejected', 'under_review'])
                    ? now()->subDays(rand(1, 15))
                    : null,
            ]));
        }

        $this->command->info('✅ ' . count($admissions) . ' candidatures créées.');
    }
}
