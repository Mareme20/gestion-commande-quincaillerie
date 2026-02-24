<?php

namespace Database\Seeders;

use App\Models\Fournisseur;
use Illuminate\Database\Seeder;

class FournisseurSeeder extends Seeder
{
    public function run(): void
    {
        $fournisseurs = [
            ['numero' => 'FOU-001', 'nom' => 'Barro Distribution', 'adresse' => 'Dakar, Médina'],
            ['numero' => 'FOU-002', 'nom' => 'Sénégal Matériaux', 'adresse' => 'Dakar, Hann'],
            ['numero' => 'FOU-003', 'nom' => 'Fer Plus', 'adresse' => 'Thiaroye, Route Nationale'],
            ['numero' => 'FOU-004', 'nom' => 'Cimenterie de l\'Avenir', 'adresse' => 'Rufisque, Zone industrielle'],
            ['numero' => 'FOU-005', 'nom' => 'Béton Express', 'adresse' => 'Pikine, Technopole'],
            ['numero' => 'FOU-006', 'nom' => 'Quincaillerie Moderne', 'adresse' => 'Guédiawaye, Golf Sud'],
        ];

        foreach ($fournisseurs as $fournisseur) {
            Fournisseur::updateOrCreate(
                ['numero' => $fournisseur['numero']],
                [
                    'nom' => $fournisseur['nom'],
                    'adresse' => $fournisseur['adresse'],
                    'archive' => false,
                ]
            );
        }
    }
}
