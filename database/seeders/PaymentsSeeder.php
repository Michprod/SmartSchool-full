<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Student;
use Illuminate\Database\Seeder;

class PaymentsSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create();
        $students = Student::all();

        if ($students->isEmpty()) {
            $this->command->warn('Aucun élève trouvé. Lancez d\'abord StudentsSeeder.');
            return;
        }

        $paymentTypes = ['tuition', 'registration', 'exam', 'uniform', 'transport'];
        $statuses = ['completed', 'completed', 'completed', 'pending', 'failed'];
        $methods = ['mobile_money', 'mobile_money', 'cash', 'bank_transfer'];
        $providers = ['m_pesa', 'orange_money', 'airtel_money'];

        $count = 0;
        foreach ($students as $student) {
            // Frais de scolarité (toujours présent)
            Payment::create([
                'student_id'             => $student->id,
                'amount'                 => $faker->randomElement([150000, 200000, 250000, 300000]),
                'currency'               => 'CDF',
                'type'                   => 'tuition',
                'status'                 => $faker->randomElement(['completed', 'completed', 'pending']),
                'payment_method'         => $faker->randomElement($methods),
                'mobile_money_provider'  => $faker->randomElement($providers),
                'transaction_id'         => 'TXN-' . strtoupper($faker->bothify('??#####')),
                'reference'              => 'REF-' . strtoupper($faker->bothify('####??')),
                'description'            => 'Frais de scolarité - Trimestre 1 - ' . date('Y'),
                'due_date'               => date('Y-09-15'),
                'paid_at'                => $faker->boolean(70) ? now()->subDays(rand(1, 60)) : null,
            ]);
            $count++;

            // Frais d'inscription (seulement pour ~70% des élèves)
            if ($faker->boolean(70)) {
                Payment::create([
                    'student_id'             => $student->id,
                    'amount'                 => $faker->randomElement([25000, 30000, 50000]),
                    'currency'               => 'CDF',
                    'type'                   => 'registration',
                    'status'                 => 'completed',
                    'payment_method'         => 'cash',
                    'mobile_money_provider'  => null,
                    'transaction_id'         => 'TXN-' . strtoupper($faker->bothify('??#####')),
                    'reference'              => 'REF-INS-' . strtoupper($faker->bothify('####')),
                    'description'            => 'Frais d\'inscription - Année scolaire 2025-2026',
                    'due_date'               => date('Y-09-01'),
                    'paid_at'                => now()->subDays(rand(60, 120)),
                ]);
                $count++;
            }

            // Frais d'examen (pour ~50% des élèves)
            if ($faker->boolean(50)) {
                Payment::create([
                    'student_id'             => $student->id,
                    'amount'                 => $faker->randomElement([10000, 15000, 20000]),
                    'currency'               => 'CDF',
                    'type'                   => 'exam',
                    'status'                 => $faker->randomElement(['completed', 'pending']),
                    'payment_method'         => $faker->randomElement(['mobile_money', 'cash']),
                    'mobile_money_provider'  => $faker->randomElement($providers),
                    'transaction_id'         => 'TXN-' . strtoupper($faker->bothify('??#####')),
                    'reference'              => 'REF-EX-' . strtoupper($faker->bothify('####')),
                    'description'            => 'Frais d\'examen - Décembre ' . date('Y'),
                    'due_date'               => date('Y-12-01'),
                    'paid_at'                => $faker->boolean(60) ? now()->subDays(rand(1, 30)) : null,
                ]);
                $count++;
            }

            // Frais de transport (pour ~40% des élèves)
            if ($faker->boolean(40)) {
                Payment::create([
                    'student_id'             => $student->id,
                    'amount'                 => $faker->randomElement([30000, 45000, 60000]),
                    'currency'               => 'CDF',
                    'type'                   => 'transport',
                    'status'                 => $faker->randomElement(['completed', 'pending']),
                    'payment_method'         => $faker->randomElement(['mobile_money', 'cash']),
                    'mobile_money_provider'  => $faker->randomElement($providers),
                    'transaction_id'         => 'TXN-' . strtoupper($faker->bothify('??#####')),
                    'reference'              => 'REF-TR-' . strtoupper($faker->bothify('####')),
                    'description'            => 'Frais de transport - Trimestre 1',
                    'due_date'               => date('Y-09-30'),
                    'paid_at'                => $faker->boolean(70) ? now()->subDays(rand(1, 45)) : null,
                ]);
                $count++;
            }

            // Paiement USD (pour ~30% des élèves en secondaire)
            if ($faker->boolean(30)) {
                Payment::create([
                    'student_id'             => $student->id,
                    'amount'                 => $faker->randomElement([50, 75, 100, 150]),
                    'currency'               => 'USD',
                    'type'                   => 'tuition',
                    'status'                 => $faker->randomElement(['completed', 'pending']),
                    'payment_method'         => $faker->randomElement(['bank_transfer', 'mobile_money']),
                    'mobile_money_provider'  => 'm_pesa',
                    'transaction_id'         => 'TXN-USD-' . strtoupper($faker->bothify('??#####')),
                    'reference'              => 'REF-USD-' . strtoupper($faker->bothify('####')),
                    'description'            => 'Frais complémentaires USD - Trimestre 1',
                    'due_date'               => date('Y-10-15'),
                    'paid_at'                => $faker->boolean(65) ? now()->subDays(rand(1, 30)) : null,
                ]);
                $count++;
            }
        }

        $this->command->info("✅ {$count} paiements créés.");
    }
}
