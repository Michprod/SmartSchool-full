<?php

namespace Database\Seeders;

use App\Models\FeeRate;
use App\Models\FeeType;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class FeeRatesSeeder extends Seeder
{
    public function run(): void
    {
        $academicYear = '2025-2026';

        $setting = Setting::where('key', 'school_settings')->first();
        $value = $setting?->value;

        if (is_array($value) && ! empty($value['currentYear'])) {
            $academicYear = (string) $value['currentYear'];
        }

        // Le frontend/SetupStatus a surtout besoin d'au moins 1 fee_rate active.
        $ratesByCode = [
            'tuition' => 50000,
            'registration' => 15000,
        ];

        foreach ($ratesByCode as $feeTypeCode => $amount) {
            $feeType = FeeType::where('code', $feeTypeCode)->first();
            if (! $feeType) {
                continue;
            }

            FeeRate::firstOrCreate(
                [
                    'fee_type_id' => $feeType->id,
                    'academic_year' => $academicYear,
                    'currency' => 'CDF',
                    'grade_level_id' => null,
                ],
                [
                    'amount' => $amount,
                    'is_active' => true,
                ]
            );
        }
    }
}

