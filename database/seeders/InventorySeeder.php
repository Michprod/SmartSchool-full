<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('inventory_items')->truncate();

        $items = [
            [
                'name' => 'Ordinateurs Portables Dell',
                'category' => 'Informatique',
                'quantity' => 25,
                'location' => 'Salle Informatique 1',
                'status' => 'in_stock',
            ],
            [
                'name' => 'Projecteurs Epson',
                'category' => 'Audiovisuel',
                'quantity' => 12,
                'location' => 'Bâtiment Principal',
                'status' => 'in_use',
            ],
            [
                'name' => 'Chaises Ergonomiques',
                'category' => 'Mobilier',
                'quantity' => 150,
                'location' => 'Salles de classe 1-10',
                'status' => 'in_stock',
            ],
            [
                'name' => 'Microscopes Optiques',
                'category' => 'Laboratoire',
                'quantity' => 8,
                'location' => 'Laboratoire de Sciences',
                'status' => 'under_maintenance',
            ],
            [
                'name' => 'Tableaux Blancs',
                'category' => 'Mobilier',
                'quantity' => 20,
                'location' => 'Salles de classe',
                'status' => 'in_use',
            ],
            [
                'name' => 'Imprimantes HP LaserJet',
                'category' => 'Informatique',
                'quantity' => 5,
                'location' => 'Administration',
                'status' => 'in_use',
            ]
        ];

        foreach ($items as $item) {
            InventoryItem::create($item);
        }
    }
}
