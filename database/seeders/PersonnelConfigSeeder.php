<?php

namespace Database\Seeders;

use App\Models\PersonnelConfigItem;
use Illuminate\Database\Seeder;

class PersonnelConfigSeeder extends Seeder
{
    public function run(): void
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

