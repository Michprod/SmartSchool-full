<?php

namespace Database\Seeders;

use App\Models\RdcCity;
use App\Models\RdcCommune;
use App\Models\RdcProvince;
use Illuminate\Database\Seeder;

class RdcGeoSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Kinshasa' => [
                'Kinshasa' => ['Gombe', 'Barumbu', 'Kinshasa', 'Kintambo', 'Limete', 'Lingwala', 'Matete', 'Ngaliema', 'Ngaba', 'Ngiri-Ngiri', 'Nsele', 'Selembao'],
            ],
            'Kongo-Central' => [
                'Matadi' => ['Matadi', 'Mvuzi'],
                'Boma' => ['Boma', 'Kalamu'],
            ],
            'Haut-Katanga' => [
                'Lubumbashi' => ['Lubumbashi', 'Kampemba', 'Katuba'],
            ],
            'Nord-Kivu' => [
                'Goma' => ['Goma', 'Karisimbi'],
            ],
            'Sud-Kivu' => [
                'Bukavu' => ['Ibanda', 'Kadutu'],
            ],
            'Kasaï-Oriental' => [
                'Mbuji-Mayi' => ['Bipemba', 'Diulu'],
            ],
            'Lualaba' => [
                'Kolwezi' => ['Dilala', 'Manika'],
            ],
        ];

        $allProvinces = [
            'Kinshasa', 'Kongo-Central', 'Kwango', 'Kwilu', 'Mai-Ndombe',
            'Kasaï', 'Kasaï-Central', 'Kasaï-Oriental', 'Lomami', 'Sankuru',
            'Maniema', 'Sud-Kivu', 'Nord-Kivu', 'Ituri', 'Haut-Uélé',
            'Tshopo', 'Bas-Uélé', 'Nord-Ubangi', 'Mongala', 'Équateur',
            'Tshuapa', 'Tanganyika', 'Haut-Lomami', 'Lualaba', 'Haut-Katanga',
            'Tanganyika', 'Sud-Ubangi',
        ];

        foreach (array_unique($allProvinces) as $provinceName) {
            $province = RdcProvince::firstOrCreate(['name' => $provinceName]);

            if (! isset($data[$provinceName])) {
                RdcCity::firstOrCreate(
                    ['province_id' => $province->id, 'name' => $provinceName],
                    ['province_id' => $province->id, 'name' => $provinceName]
                );
                continue;
            }

            foreach ($data[$provinceName] as $cityName => $communes) {
                $city = RdcCity::firstOrCreate(
                    ['province_id' => $province->id, 'name' => $cityName],
                    ['province_id' => $province->id, 'name' => $cityName]
                );

                foreach ($communes as $communeName) {
                    RdcCommune::firstOrCreate(
                        ['city_id' => $city->id, 'name' => $communeName],
                        ['city_id' => $city->id, 'name' => $communeName]
                    );
                }
            }
        }

        $this->command?->info('✅ Référentiel géographique RDC initialisé.');
    }
}
