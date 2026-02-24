<?php

namespace Database\Seeders;

use App\Models\Commande;
use App\Models\Produit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CommandeSeeder extends Seeder
{
    public function run(): void
    {
        if (Commande::count() > 0) {
            return;
        }

        $produitsParFournisseur = Produit::where('archive', false)
            ->whereNotNull('fournisseur_id')
            ->get()
            ->groupBy('fournisseur_id');

        foreach ($produitsParFournisseur as $fournisseurId => $produits) {
            $nombreCommandes = random_int(2, 4);

            for ($i = 0; $i < $nombreCommandes; $i++) {
                $dateCommande = Carbon::today()->subDays(random_int(10, 90));
                $dateLivraisonPrevue = $dateCommande->copy()->addDays(random_int(3, 14));

                $etat = $this->etatAleatoire();
                $dateLivraisonReelle = null;

                if (in_array($etat, ['livre', 'paye'], true)) {
                    $dateLivraisonReelle = $dateLivraisonPrevue->copy()->addDays(random_int(0, 4));
                }

                $commande = Commande::create([
                    'fournisseur_id' => $fournisseurId,
                    'date_commande' => $dateCommande,
                    'date_livraison_prevue' => $dateLivraisonPrevue,
                    'date_livraison_reelle' => $dateLivraisonReelle,
                    'montant_total' => 0,
                    'etat' => $etat,
                ]);

                $selection = $produits->shuffle()->take(min($produits->count(), random_int(1, 3)));
                $montantTotal = 0;
                $attachData = [];

                foreach ($selection as $produit) {
                    $quantite = random_int(5, 80);
                    $prixAchat = round((float) $produit->prix_unitaire * (random_int(90, 112) / 100), 2);
                    $montantTotal += $quantite * $prixAchat;

                    $attachData[$produit->id] = [
                        'fournisseur_id' => $fournisseurId,
                        'quantite' => $quantite,
                        'prix_achat' => $prixAchat,
                    ];
                }

                $commande->produits()->attach($attachData);
                $commande->update(['montant_total' => round($montantTotal, 2)]);
            }
        }
    }

    private function etatAleatoire(): string
    {
        $pool = ['en_cours', 'en_cours', 'livre', 'livre', 'paye', 'annule'];

        return $pool[array_rand($pool)];
    }
}
