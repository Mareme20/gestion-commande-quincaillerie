<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Fournisseur;
use App\Models\Produit;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AlertService
{
    public function getAlerts(): Collection
    {
        $today = Carbon::today();
        $alertes = collect();

        $retards = Commande::with('fournisseur')
            ->where('etat', Commande::ETAT_VALIDEE)
            ->whereDate('date_livraison_prevue', '<', $today)
            ->orderBy('date_livraison_prevue')
            ->limit(10)
            ->get();

        foreach ($retards as $commande) {
            $alertes->push($this->buildAlerte([
                'type' => 'retard_livraison',
                'niveau' => 'danger',
                'message' => sprintf(
                    'Commande CMD-%06d en retard de livraison.',
                    $commande->id
                ),
                'lien' => route('commandes.show', $commande->id),
                'signature' => 'retard_livraison:commande:' . $commande->id,
            ]));
        }

        $commandesRecues = Commande::with('versements')
            ->where('etat', Commande::ETAT_RECUE)
            ->whereNotNull('date_livraison_reelle')
            ->get();

        foreach ($commandesRecues as $commande) {
            $days = Carbon::parse($commande->date_livraison_reelle)->diffInDays($today, false);
            if ($days < 5) {
                continue;
            }

            $theorique = min(3, intdiv($days, 5));
            $actuel = $commande->versements->count();

            if ($actuel < $theorique && $commande->montantRestant() > 0) {
                $alertes->push($this->buildAlerte([
                    'type' => 'echeance_versement',
                    'niveau' => 'warning',
                    'message' => sprintf(
                        'Echeance versement en retard pour CMD-%06d.',
                        $commande->id
                    ),
                    'lien' => route('commandes.show', $commande->id),
                    'signature' => 'echeance_versement:commande:' . $commande->id . ':palier:' . $theorique,
                ]));
            }
        }

        $seuilStock = (int) config('alerts.stock_critique_seuil', 10);
        $stocksCritiques = Produit::query()
            ->where('archive', false)
            ->where('quantite_stock', '<', $seuilStock)
            ->orderBy('quantite_stock')
            ->limit(10)
            ->get();

        foreach ($stocksCritiques as $produit) {
            $alertes->push($this->buildAlerte([
                'type' => 'stock_critique',
                'niveau' => 'warning',
                'message' => sprintf(
                    'Stock critique: %s (%d).',
                    $produit->designation,
                    $produit->quantite_stock
                ),
                'lien' => route('produits.show', $produit->id),
                'signature' => 'stock_critique:produit:' . $produit->id,
            ]));
        }

        $seuilDette = (float) config('alerts.dette_fournisseur_seuil', 1000000);
        $dettesElevees = Fournisseur::with(['commandes.versements'])
            ->where('archive', false)
            ->get()
            ->map(function (Fournisseur $fournisseur) {
                $dette = $fournisseur->commandes
                    ->whereIn('etat', [Commande::ETAT_VALIDEE, Commande::ETAT_RECUE])
                    ->sum(fn (Commande $commande) => $commande->montantRestant());

                return ['fournisseur' => $fournisseur, 'dette' => $dette];
            })
            ->filter(fn (array $item) => $item['dette'] > $seuilDette)
            ->sortByDesc('dette')
            ->take(5);

        foreach ($dettesElevees as $item) {
            /** @var \App\Models\Fournisseur $fournisseur */
            $fournisseur = $item['fournisseur'];
            $dette = (float) $item['dette'];

            $alertes->push($this->buildAlerte([
                'type' => 'dette_fournisseur',
                'niveau' => 'danger',
                'message' => sprintf(
                    'Dette elevee chez %s: %s FCFA.',
                    $fournisseur->nom,
                    number_format($dette, 0, ',', ' ')
                ),
                'lien' => route('fournisseurs.show', $fournisseur->id),
                'signature' => 'dette_fournisseur:' . $fournisseur->id,
            ]));
        }

        return $alertes->values();
    }

    private function buildAlerte(array $alerte): array
    {
        return [
            'type' => $alerte['type'] ?? 'inconnu',
            'niveau' => $alerte['niveau'] ?? 'warning',
            'message' => $alerte['message'] ?? '',
            'lien' => $alerte['lien'] ?? null,
            'signature' => $alerte['signature'] ?? sha1(($alerte['type'] ?? '') . '|' . ($alerte['message'] ?? '')),
        ];
    }
}
