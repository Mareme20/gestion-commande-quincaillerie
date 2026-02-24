<?php

namespace Database\Seeders;

use App\Models\Commande;
use App\Models\Versement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class VersementSeeder extends Seeder
{
    public function run(): void
    {
        $commandes = Commande::with('versements')
            ->whereIn('etat', ['recue', 'cloturee'])
            ->whereNotNull('date_livraison_reelle')
            ->get();

        foreach ($commandes as $commande) {
            if ($commande->versements()->exists()) {
                continue;
            }

            if ($commande->etat === 'cloturee') {
                $this->seedCommandePayee($commande);
                continue;
            }

            $this->seedCommandeLivree($commande);
        }
    }

    private function seedCommandePayee(Commande $commande): void
    {
        $nombreVersements = random_int(1, 3);
        $base = round((float) $commande->montant_total / $nombreVersements, 2);
        $versements = array_fill(0, $nombreVersements, $base);

        $sommePartielle = round(array_sum($versements), 2);
        $ecart = round((float) $commande->montant_total - $sommePartielle, 2);
        $versements[$nombreVersements - 1] = round($versements[$nombreVersements - 1] + $ecart, 2);

        $dateBase = Carbon::parse($commande->date_livraison_reelle)->addDays(5);

        foreach ($versements as $index => $montant) {
            $dateVersement = $dateBase->copy()->addDays($index * 5);
            $this->createVersement($commande->id, $dateVersement, $montant);
        }
    }

    private function seedCommandeLivree(Commande $commande): void
    {
        $dateVersement = Carbon::parse($commande->date_livraison_reelle)->addDays(5 + random_int(0, 4));
        $montant = round((float) $commande->montant_total * (random_int(25, 60) / 100), 2);
        $montant = min($montant, (float) $commande->montant_total);

        $this->createVersement($commande->id, $dateVersement, $montant);
    }

    private function createVersement(int $commandeId, Carbon $dateVersement, float $montant): void
    {
        $countMois = Versement::whereYear('date_versement', $dateVersement->year)
            ->whereMonth('date_versement', $dateVersement->month)
            ->count();

        Versement::create([
            'commande_id' => $commandeId,
            'numero_versement' => 'VERS-' . $dateVersement->format('Ym') . '-' . str_pad((string) ($countMois + 1), 4, '0', STR_PAD_LEFT),
            'date_versement' => $dateVersement->toDateString(),
            'montant' => max(0, round($montant, 2)),
        ]);
    }
}
