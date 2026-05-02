<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentsSeeder extends Seeder
{
    public function run(): void
    {
        $classes = SchoolClass::all()->keyBy('name');

        $students = [
            // 1ère Maternelle
            ['first_name' => 'Eline',     'last_name' => 'Kabila',    'gender' => 'F', 'dob' => '2021-03-10', 'class' => '1ère Maternelle', 'guardian' => 'Pierre Kabila',    'guardian_phone' => '+243 810 001 001', 'relation' => 'Père',  'city' => 'Kinshasa'],
            ['first_name' => 'Samuel',    'last_name' => 'Tshilombo', 'gender' => 'M', 'dob' => '2021-07-22', 'class' => '1ère Maternelle', 'guardian' => 'Anne Tshilombo',   'guardian_phone' => '+243 810 001 002', 'relation' => 'Mère',  'city' => 'Kinshasa'],

            // 2ème Maternelle
            ['first_name' => 'Grâce',     'last_name' => 'Ngoy',      'gender' => 'F', 'dob' => '2020-05-18', 'class' => '2ème Maternelle', 'guardian' => 'Didier Ngoy',      'guardian_phone' => '+243 810 001 003', 'relation' => 'Père',  'city' => 'Kinshasa'],
            ['first_name' => 'Junior',    'last_name' => 'Mwila',     'gender' => 'M', 'dob' => '2020-01-30', 'class' => '2ème Maternelle', 'guardian' => 'Sophie Mwila',     'guardian_phone' => '+243 810 001 004', 'relation' => 'Mère',  'city' => 'Kinshasa'],

            // 3ème Maternelle
            ['first_name' => 'Merveille', 'last_name' => 'Kabuya',    'gender' => 'F', 'dob' => '2019-09-05', 'class' => '3ème Maternelle', 'guardian' => 'Michel Kabuya',    'guardian_phone' => '+243 810 001 005', 'relation' => 'Père',  'city' => 'Gombe'],
            ['first_name' => 'Nathan',    'last_name' => 'Kalala',    'gender' => 'M', 'dob' => '2019-11-14', 'class' => '3ème Maternelle', 'guardian' => 'Christine Kalala', 'guardian_phone' => '+243 810 001 006', 'relation' => 'Mère',  'city' => 'Gombe'],

            // 1ère Primaire
            ['first_name' => 'David',     'last_name' => 'Ilunga',    'gender' => 'M', 'dob' => '2018-04-12', 'class' => '1ère Primaire', 'guardian' => 'Robert Ilunga',    'guardian_phone' => '+243 820 001 001', 'relation' => 'Père',  'city' => 'Kinshasa'],
            ['first_name' => 'Esther',    'last_name' => 'Mutombo',   'gender' => 'F', 'dob' => '2018-08-25', 'class' => '1ère Primaire', 'guardian' => 'Yvette Mutombo',   'guardian_phone' => '+243 820 001 002', 'relation' => 'Mère',  'city' => 'Kinshasa'],
            ['first_name' => 'Aaron',     'last_name' => 'Lukusa',    'gender' => 'M', 'dob' => '2018-02-03', 'class' => '1ère Primaire', 'guardian' => 'Jonas Lukusa',     'guardian_phone' => '+243 820 001 003', 'relation' => 'Père',  'city' => 'Ngaliema'],

            // 2ème Primaire
            ['first_name' => 'Fabiola',   'last_name' => 'Kibambala', 'gender' => 'F', 'dob' => '2017-06-17', 'class' => '2ème Primaire', 'guardian' => 'Albert Kibambala', 'guardian_phone' => '+243 820 001 004', 'relation' => 'Père',  'city' => 'Kinshasa'],
            ['first_name' => 'Isaac',     'last_name' => 'Mwamba',    'gender' => 'M', 'dob' => '2017-03-22', 'class' => '2ème Primaire', 'guardian' => 'Pauline Mwamba',   'guardian_phone' => '+243 820 001 005', 'relation' => 'Mère',  'city' => 'Limete'],
            ['first_name' => 'Rachel',    'last_name' => 'Kafuta',    'gender' => 'F', 'dob' => '2017-10-08', 'class' => '2ème Primaire', 'guardian' => 'Daniel Kafuta',    'guardian_phone' => '+243 820 001 006', 'relation' => 'Père',  'city' => 'Kinshasa'],

            // 3ème Primaire
            ['first_name' => 'Josué',     'last_name' => 'Kasongo',   'gender' => 'M', 'dob' => '2016-01-15', 'class' => '3ème Primaire', 'guardian' => 'Céline Kasongo',   'guardian_phone' => '+243 820 001 007', 'relation' => 'Mère',  'city' => 'Kinshasa'],
            ['first_name' => 'Laetitia',  'last_name' => 'Ntumba',    'gender' => 'F', 'dob' => '2016-05-29', 'class' => '3ème Primaire', 'guardian' => 'Serge Ntumba',     'guardian_phone' => '+243 820 001 008', 'relation' => 'Père',  'city' => 'Masina'],
            ['first_name' => 'Caleb',     'last_name' => 'Kabongo',   'gender' => 'M', 'dob' => '2016-09-11', 'class' => '3ème Primaire', 'guardian' => 'Fifi Kabongo',     'guardian_phone' => '+243 820 001 009', 'relation' => 'Mère',  'city' => 'Kinshasa'],

            // 4ème Primaire
            ['first_name' => 'Prisca',    'last_name' => 'Lubangi',   'gender' => 'F', 'dob' => '2015-07-04', 'class' => '4ème Primaire', 'guardian' => 'Noé Lubangi',      'guardian_phone' => '+243 820 001 010', 'relation' => 'Père',  'city' => 'Kinshasa'],
            ['first_name' => 'Benjamin',  'last_name' => 'Mbuyi',     'gender' => 'M', 'dob' => '2015-11-19', 'class' => '4ème Primaire', 'guardian' => 'Judith Mbuyi',     'guardian_phone' => '+243 820 001 011', 'relation' => 'Mère',  'city' => 'Lemba'],
            ['first_name' => 'Deborah',   'last_name' => 'Kilongo',   'gender' => 'F', 'dob' => '2015-03-28', 'class' => '4ème Primaire', 'guardian' => 'Blaise Kilongo',   'guardian_phone' => '+243 820 001 012', 'relation' => 'Père',  'city' => 'Kinshasa'],

            // 5ème Primaire
            ['first_name' => 'Daniel',    'last_name' => 'Banza',     'gender' => 'M', 'dob' => '2014-02-14', 'class' => '5ème Primaire', 'guardian' => 'Chantal Banza',    'guardian_phone' => '+243 820 001 013', 'relation' => 'Mère',  'city' => 'Kinshasa'],
            ['first_name' => 'Sarah',     'last_name' => 'Mukeba',    'gender' => 'F', 'dob' => '2014-06-30', 'class' => '5ème Primaire', 'guardian' => 'Luc Mukeba',       'guardian_phone' => '+243 820 001 014', 'relation' => 'Père',  'city' => 'Kalamu'],
            ['first_name' => 'Mathieu',   'last_name' => 'Ngalula',   'gender' => 'M', 'dob' => '2014-09-22', 'class' => '5ème Primaire', 'guardian' => 'Clara Ngalula',    'guardian_phone' => '+243 820 001 015', 'relation' => 'Mère',  'city' => 'Kinshasa'],

            // 6ème Primaire
            ['first_name' => 'Miriam',    'last_name' => 'Tshimanga', 'gender' => 'F', 'dob' => '2013-04-05', 'class' => '6ème Primaire', 'guardian' => 'Pierre Tshimanga', 'guardian_phone' => '+243 820 001 016', 'relation' => 'Père',  'city' => 'Kinshasa'],
            ['first_name' => 'Théodore',  'last_name' => 'Shambuyi',  'gender' => 'M', 'dob' => '2013-08-17', 'class' => '6ème Primaire', 'guardian' => 'Martine Shambuyi', 'guardian_phone' => '+243 820 001 017', 'relation' => 'Mère',  'city' => 'Ngaliema'],
            ['first_name' => 'Olivia',    'last_name' => 'Maloba',    'gender' => 'F', 'dob' => '2013-12-01', 'class' => '6ème Primaire', 'guardian' => 'Kevin Maloba',     'guardian_phone' => '+243 820 001 018', 'relation' => 'Père',  'city' => 'Kinshasa'],

            // 7ème Éducation de Base
            ['first_name' => 'Princesse', 'last_name' => 'Kanda',     'gender' => 'F', 'dob' => '2012-03-08', 'class' => '7ème Éducation de Base', 'guardian' => 'Hervé Kanda',     'guardian_phone' => '+243 850 001 001', 'relation' => 'Père',  'city' => 'Kinshasa'],
            ['first_name' => 'Étienne',   'last_name' => 'Lubaba',    'gender' => 'M', 'dob' => '2012-07-24', 'class' => '7ème Éducation de Base', 'guardian' => 'Nicole Lubaba',   'guardian_phone' => '+243 850 001 002', 'relation' => 'Mère',  'city' => 'Kintambo'],
            ['first_name' => 'Ange',      'last_name' => 'Mbaya',     'gender' => 'F', 'dob' => '2012-11-13', 'class' => '7ème Éducation de Base', 'guardian' => 'Charles Mbaya',   'guardian_phone' => '+243 850 001 003', 'relation' => 'Père',  'city' => 'Kinshasa'],

            // 8ème Éducation de Base
            ['first_name' => 'Yves',      'last_name' => 'Nkongolo',  'gender' => 'M', 'dob' => '2011-01-19', 'class' => '8ème Éducation de Base', 'guardian' => 'Rose Nkongolo',   'guardian_phone' => '+243 850 001 004', 'relation' => 'Mère',  'city' => 'Kinshasa'],
            ['first_name' => 'Claudine',  'last_name' => 'Bakama',    'gender' => 'F', 'dob' => '2011-05-07', 'class' => '8ème Éducation de Base', 'guardian' => 'Félix Bakama',    'guardian_phone' => '+243 850 001 005', 'relation' => 'Père',  'city' => 'Gombe'],
            ['first_name' => 'Christian', 'last_name' => 'Lufungula', 'gender' => 'M', 'dob' => '2011-09-28', 'class' => '8ème Éducation de Base', 'guardian' => 'Claudette Luf.',  'guardian_phone' => '+243 850 001 006', 'relation' => 'Mère',  'city' => 'Kinshasa'],

            // 1ère Humanités
            ['first_name' => 'Gloire',    'last_name' => 'Kalonji',   'gender' => 'F', 'dob' => '2010-02-11', 'class' => '1ère Humanités', 'guardian' => 'Didier Kalonji',  'guardian_phone' => '+243 850 001 007', 'relation' => 'Père',  'city' => 'Kinshasa'],
            ['first_name' => 'Ephraïm',   'last_name' => 'Mwangu',    'gender' => 'M', 'dob' => '2010-06-25', 'class' => '1ère Humanités', 'guardian' => 'Béatrice Mwangu', 'guardian_phone' => '+243 850 001 008', 'relation' => 'Mère',  'city' => 'Bandalungwa'],
            ['first_name' => 'Solange',   'last_name' => 'Kabambi',   'gender' => 'F', 'dob' => '2010-10-14', 'class' => '1ère Humanités', 'guardian' => 'Victor Kabambi',  'guardian_phone' => '+243 850 001 009', 'relation' => 'Père',  'city' => 'Kinshasa'],

            // 2ème Humanités
            ['first_name' => 'Jonathan',  'last_name' => 'Kaseba',    'gender' => 'M', 'dob' => '2009-03-03', 'class' => '2ème Humanités', 'guardian' => 'Aimée Kaseba',    'guardian_phone' => '+243 850 001 010', 'relation' => 'Mère',  'city' => 'Kinshasa'],
            ['first_name' => 'Chance',    'last_name' => 'Musonda',   'gender' => 'M', 'dob' => '2009-07-16', 'class' => '2ème Humanités', 'guardian' => 'Laurent Musonda', 'guardian_phone' => '+243 850 001 011', 'relation' => 'Père',  'city' => 'Limete'],
            ['first_name' => 'Victoire',  'last_name' => 'Tambwe',    'gender' => 'F', 'dob' => '2009-11-29', 'class' => '2ème Humanités', 'guardian' => 'Sylvie Tambwe',   'guardian_phone' => '+243 850 001 012', 'relation' => 'Mère',  'city' => 'Kinshasa'],

            // 3ème Humanités
            ['first_name' => 'Pacifique', 'last_name' => 'Kahindo',   'gender' => 'M', 'dob' => '2008-04-20', 'class' => '3ème Humanités', 'guardian' => 'Marie Kahindo',   'guardian_phone' => '+243 850 001 013', 'relation' => 'Mère',  'city' => 'Kinshasa'],
            ['first_name' => 'Amour',     'last_name' => 'Mbunga',    'gender' => 'F', 'dob' => '2008-08-09', 'class' => '3ème Humanités', 'guardian' => 'Pascal Mbunga',   'guardian_phone' => '+243 850 001 014', 'relation' => 'Père',  'city' => 'Masina'],
            ['first_name' => 'Gérard',    'last_name' => 'Kyungu',    'gender' => 'M', 'dob' => '2008-12-18', 'class' => '3ème Humanités', 'guardian' => 'Thérèse Kyungu',  'guardian_phone' => '+243 850 001 015', 'relation' => 'Mère',  'city' => 'Kinshasa'],

            // 4ème Humanités
            ['first_name' => 'Joëlle',    'last_name' => 'Nzuzi',     'gender' => 'F', 'dob' => '2007-01-08', 'class' => '4ème Humanités', 'guardian' => 'Antoine Nzuzi',   'guardian_phone' => '+243 850 001 016', 'relation' => 'Père',  'city' => 'Kinshasa'],
            ['first_name' => 'Exaucé',    'last_name' => 'Kayembe',   'gender' => 'M', 'dob' => '2007-05-23', 'class' => '4ème Humanités', 'guardian' => 'Hortense Kayembe','guardian_phone' => '+243 850 001 017', 'relation' => 'Mère',  'city' => 'Ngaliema'],
            ['first_name' => 'Nadège',    'last_name' => 'Mabunda',   'gender' => 'F', 'dob' => '2007-09-12', 'class' => '4ème Humanités', 'guardian' => 'Sylvain Mabunda', 'guardian_phone' => '+243 850 001 018', 'relation' => 'Père',  'city' => 'Kinshasa'],
        ];

        $count = 0;
        foreach ($students as $index => $s) {
            $class = $classes->get($s['class']);
            if (!$class) {
                $this->command->warn("Classe introuvable: {$s['class']}");
                continue;
            }

            $num = str_pad($index + 1, 4, '0', STR_PAD_LEFT);
            $matricule = 'SS2025-' . $num;
            $studentNumber = 'EL' . $num;

            Student::updateOrCreate(
                ['matricule' => $matricule],
                [
                    'student_number'  => $studentNumber,
                    'first_name'      => $s['first_name'],
                    'last_name'       => $s['last_name'],
                    'date_of_birth'   => $s['dob'],
                    'gender'          => $s['gender'],
                    'place_of_birth'  => $s['city'],
                    'nationality'     => 'Congolaise (RDC)',
                    'address'         => 'Avenue de la Paix',
                    'city'            => $s['city'],
                    'province'        => 'Kinshasa',
                    'class_id'        => $class->id,
                    'academic_year'   => '2025-2026',
                    'enrollment_date' => '2025-09-01',
                    'guardian_name'   => $s['guardian'],
                    'guardian_relation' => $s['relation'],
                    'guardian_phone'  => $s['guardian_phone'],
                    'guardian_email'  => null,
                    'status'          => 'active',
                    'is_active'       => true,
                ]
            );
            $count++;
        }

        $this->command->info("✅ {$count} élèves créés.");
    }
}
