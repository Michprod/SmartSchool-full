<?php

namespace Database\Seeders;

use App\Models\FeeType;
use App\Models\InstallmentType;
use Illuminate\Database\Seeder;

class FinanceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $feeTypes = [
            ['code' => 'tuition', 'label' => 'Frais scolaires'],
            ['code' => 'registration', 'label' => 'Frais d\'inscription'],
            ['code' => 'exam', 'label' => 'Frais d\'examen'],
            ['code' => 'uniform', 'label' => 'Uniforme'],
            ['code' => 'transport', 'label' => 'Transport'],
            ['code' => 'meal', 'label' => 'Cantine'],
            ['code' => 'other', 'label' => 'Autre'],
        ];

        foreach ($feeTypes as $row) {
            FeeType::updateOrCreate(['code' => $row['code']], $row + ['is_active' => true]);
        }

        $installments = [
            ['code' => 'single', 'label' => 'Paiement unique', 'default_count' => 1],
            ['code' => 'monthly', 'label' => 'Mensuel', 'default_count' => 10],
            ['code' => 'quarterly', 'label' => 'Trimestriel', 'default_count' => 3],
            ['code' => 'custom', 'label' => 'Personnalisé', 'default_count' => 2],
        ];

        foreach ($installments as $row) {
            InstallmentType::updateOrCreate(['code' => $row['code']], $row + ['is_active' => true]);
        }
    }
}
