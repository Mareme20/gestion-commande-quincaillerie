<?php
// database/seeders/CategorieSeeder.php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\SousCategorie;
use Illuminate\Database\Seeder;

class CategorieSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['nom' => 'Ciment', 'description' => 'Ciments de toutes marques'],
            ['nom' => 'Fer', 'description' => 'Fers à béton et autres'],
            ['nom' => 'Béton', 'description' => 'Produits en béton'],
            ['nom' => 'Matériel', 'description' => 'Matériel de construction'],
        ];

        foreach ($categories as $categorie) {
            $cat = Categorie::updateOrCreate(
                ['nom' => $categorie['nom']],
                ['description' => $categorie['description'], 'archive' => false]
            );
            
            switch ($cat->nom) {
                case 'Ciment':
                    $this->createSousCategories($cat->id, [
                        ['nom' => 'Ciment 32.5', 'description' => 'Ciment CPJ 32.5'],
                        ['nom' => 'Ciment 42.5', 'description' => 'Ciment CPJ 42.5'],
                        ['nom' => 'Ciment 52.5', 'description' => 'Ciment CPJ 52.5'],
                    ]);
                    break;
                    
                case 'Fer':
                    $this->createSousCategories($cat->id, [
                        ['nom' => 'Fer 6', 'description' => 'Fer à béton 6mm'],
                        ['nom' => 'Fer 8', 'description' => 'Fer à béton 8mm'],
                        ['nom' => 'Fer 10', 'description' => 'Fer à béton 10mm'],
                        ['nom' => 'Fer 12', 'description' => 'Fer à béton 12mm'],
                        ['nom' => 'Fer 14', 'description' => 'Fer à béton 14mm'],
                        ['nom' => 'Fer 16', 'description' => 'Fer à béton 16mm'],
                        ['nom' => 'Fer 20', 'description' => 'Fer à béton 20mm'],
                    ]);
                    break;
                    
                case 'Béton':
                    $this->createSousCategories($cat->id, [
                        ['nom' => 'Parpaing', 'description' => 'Parpaings de différentes dimensions'],
                        ['nom' => 'Dalle', 'description' => 'Dalles en béton'],
                        ['nom' => 'Pavé', 'description' => 'Pavés autobloquants'],
                    ]);
                    break;
                    
                case 'Matériel':
                    $this->createSousCategories($cat->id, [
                        ['nom' => 'Outillage', 'description' => 'Outils manuels'],
                        ['nom' => 'Équipement', 'description' => 'Équipement de protection'],
                        ['nom' => 'Quincaillerie', 'description' => 'Petite quincaillerie'],
                    ]);
                    break;
            }
        }
    }

    private function createSousCategories($categorieId, array $sousCategories): void
    {
        foreach ($sousCategories as $sousCat) {
            SousCategorie::updateOrCreate(
                [
                    'categorie_id' => $categorieId,
                    'nom' => $sousCat['nom'],
                ],
                [
                    'description' => $sousCat['description'],
                    'archive' => false,
                ]
            );
        }
    }
}
