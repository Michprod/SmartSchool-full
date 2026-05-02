<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnouncementsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
             $this->command->warn('Aucun admin trouvé pour créer des annonces. Lancez d\'abord UsersSeeder.');
             return;
        }

        $announcements = [
            [
                'created_by' => $admin->id,
                'title'      => 'Bienvenue sur la nouvelle plateforme SmartSchool',
                'message'    => 'Toute l\'équipe de direction vous souhaite la bienvenue sur notre nouveau système de gestion. N\'hésitez pas à mettre à jour vos mots de passe.',
                'type'       => 'info',
                'channels'   => json_encode(['push', 'email']),
                'recipients' => json_encode(['all']),
                'read_by'    => json_encode([]),
                'sent_at'    => now()->subDays(10),
            ],
            [
                'created_by' => $admin->id,
                'title'      => 'Rappel : Paiement des frais du T1',
                'message'    => 'Chers parents, nous vous rappelons que la date limite pour le paiement des frais de scolarité du premier trimestre est fixée au 15 de ce mois.',
                'type'       => 'warning',
                'channels'   => json_encode(['push', 'sms']),
                'recipients' => json_encode(['parent']), // cibler les rôles
                'read_by'    => json_encode([]),
                'sent_at'    => now()->subDays(2),
            ],
            [
                'created_by' => $admin->id,
                'title'      => 'Fermeture exceptionnelle ce vendredi',
                'message'    => 'Suite à des travaux de maintenance sur le réseau électrique de la commune, l\'école sera fermée exceptionnellement ce vendredi.',
                'type'       => 'error',
                'channels'   => json_encode(['push', 'sms', 'email']),
                'recipients' => json_encode(['all']),
                'read_by'    => json_encode([]),
                'sent_at'    => now()->subDays(1),
            ],
            [
                'created_by' => $admin->id,
                'title'      => 'Succès aux examens nationaux',
                'message'    => 'Félicitations à notre promotion sortante qui a réalisé un taux de réussite de 98% aux épreuves de l\'Etat !',
                'type'       => 'success',
                'channels'   => json_encode(['push', 'email']),
                'recipients' => json_encode(['all']),
                'read_by'    => json_encode([]),
                'sent_at'    => now()->subDays(25),
            ],
        ];

        foreach ($announcements as $announcement) {
            Announcement::create($announcement);
        }

        $this->command->info('✅ ' . count($announcements) . ' annonces créées.');
    }
}
