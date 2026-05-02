<?php

namespace Database\Seeders;

use App\Models\SchoolEvent;
use Illuminate\Database\Seeder;

class EventsSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'title'       => 'Réunion des parents - 1er Trimestre',
                'description' => 'Rencontre entre les parents et les professeurs titulaires pour discuter des objectifs du premier trimestre.',
                'date'        => now()->addDays(5)->setTime(14, 0)->format('Y-m-d H:i:s'),
                'location'    => 'Salle Polyvalente',
                'organizer'   => 'Direction',
                'media'       => json_encode([]),
            ],
            [
                'title'       => 'Fête de l\'Indépendance (Célébration à l\'école)',
                'description' => 'Activités culturelles, poèmes et chants pour célébrer l\'indépendance de la RDC.',
                'date'        => now()->addDays(20)->setTime(10, 0)->format('Y-m-d H:i:s'),
                'location'    => 'Cour de récréation principale',
                'organizer'   => 'Comité Culturel',
                'media'       => json_encode([]),
            ],
            [
                'title'       => 'Tournoi de Football Inter-classes',
                'description' => 'Début du championnat de football pour les classes du secondaire.',
                'date'        => now()->addDays(35)->setTime(15, 30)->format('Y-m-d H:i:s'),
                'location'    => 'Terrain de sport',
                'organizer'   => 'Département d\'Éducation Physique',
                'media'       => json_encode([]),
            ],
            [
                'title'       => 'Excursion Botanique a Kisantu',
                'description' => 'Visite du jardin botanique de Kisantu pour les élèves de 4ème. Autorisation parentale requise.',
                'date'        => now()->addDays(50)->setTime(07, 0)->format('Y-m-d H:i:s'),
                'location'    => 'Jardin Botanique de Kisantu',
                'organizer'   => 'Département de Sciences',
                'media'       => json_encode([]),
            ],
             [
                'title'       => 'Remise des bulletins',
                'description' => 'Proclamation des résultats du premier trimestre.',
                'date'        => now()->addDays(80)->setTime(12, 0)->format('Y-m-d H:i:s'),
                'location'    => 'Dans chaque classe',
                'organizer'   => 'Direction des Études',
                'media'       => json_encode([]),
            ],
        ];

        foreach ($events as $event) {
            SchoolEvent::create($event);
        }

        $this->command->info('✅ ' . count($events) . ' événements créés.');
    }
}
