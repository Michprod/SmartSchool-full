<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SchoolSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Structure attendue par le frontend (SchoolConfigPage) et BulletinAccessService.
        $value = [
            'schoolName' => 'SmartSchool RDC',
            'schoolCode' => 'SS-RDC',
            'email' => 'contact@smartschool.cd',
            'phone' => '+243 999 000 001',
            'address' => null,
            'city' => null,
            'province' => null,
            'currentYear' => '2025-2026',
            'block_bulletin_unpaid' => true,
        ];

        Setting::updateOrCreate(
            ['key' => 'school_settings'],
            ['value' => $value]
        );
    }
}

