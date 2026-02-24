<?php

namespace Database\Seeders;

use App\Models\Fournisseur;
use App\Models\Produit;
use App\Models\SousCategorie;
use Illuminate\Database\Seeder;

class ProduitSeeder extends Seeder
{
    public function run(): void
    {
        $fournisseurs = Fournisseur::where('archive', false)->orderBy('id')->get();
        $sousCategories = SousCategorie::where('archive', false)->get()->keyBy('nom');

        if ($fournisseurs->isEmpty() || $sousCategories->isEmpty()) {
            return;
        }

        $produits = [
            ['code' => 'CIM-325-50', 'designation' => 'Ciment 32.5 - Sac 50kg', 'sous_categorie' => 'Ciment 32.5', 'prix_unitaire' => 4200],
            ['code' => 'CIM-425-50', 'designation' => 'Ciment 42.5 - Sac 50kg', 'sous_categorie' => 'Ciment 42.5', 'prix_unitaire' => 4900],
            ['code' => 'FER-008-12', 'designation' => 'Fer de 8 - Barre 12m', 'sous_categorie' => 'Fer 8', 'prix_unitaire' => 2800],
            ['code' => 'FER-010-12', 'designation' => 'Fer de 10 - Barre 12m', 'sous_categorie' => 'Fer 10', 'prix_unitaire' => 4100],
            ['code' => 'FER-012-12', 'designation' => 'Fer de 12 - Barre 12m', 'sous_categorie' => 'Fer 12', 'prix_unitaire' => 5600],
            ['code' => 'FER-016-12', 'designation' => 'Fer de 16 - Barre 12m', 'sous_categorie' => 'Fer 16', 'prix_unitaire' => 9800],
            ['code' => 'PARP-15', 'designation' => 'Parpaing 15', 'sous_categorie' => 'Parpaing', 'prix_unitaire' => 750],
            ['code' => 'PAVE-UNI', 'designation' => 'Pavé autobloquant standard', 'sous_categorie' => 'Pavé', 'prix_unitaire' => 1200],
            ['code' => 'DAL-40', 'designation' => 'Dalle béton 40x40', 'sous_categorie' => 'Dalle', 'prix_unitaire' => 3500],
            ['code' => 'OUT-PELLE', 'designation' => 'Pelle acier renforcé', 'sous_categorie' => 'Outillage', 'prix_unitaire' => 6500],
            ['code' => 'EPI-CASQ', 'designation' => 'Casque de chantier', 'sous_categorie' => 'Équipement', 'prix_unitaire' => 3800],
            ['code' => 'QUI-CLOU', 'designation' => 'Clous 100mm (boîte)', 'sous_categorie' => 'Quincaillerie', 'prix_unitaire' => 2500],
        ];

        foreach ($produits as $index => $item) {
            $sousCategorie = $sousCategories->get($item['sous_categorie']);
            if (!$sousCategorie) {
                continue;
            }

            $fournisseur = $fournisseurs[$index % $fournisseurs->count()];

            Produit::updateOrCreate(
                ['code' => $item['code']],
                [
                    'sous_categorie_id' => $sousCategorie->id,
                    'fournisseur_id' => $fournisseur->id,
                    'designation' => $item['designation'],
                    'quantite_stock' => random_int(40, 300),
                    'prix_unitaire' => $item['prix_unitaire'],
                    'archive' => false,
                ]
            );
        }
    }
}
