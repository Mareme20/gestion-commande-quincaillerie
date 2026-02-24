<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\Versement;
use App\Models\Fournisseur;
use App\Models\Produit;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function statistiques(Request $request)
    {
        $periode = $request->input('periode', 'journee'); // journee, semaine, mois, annee
        
        switch ($periode) {
            case 'semaine':
                $dateDebut = Carbon::now()->startOfWeek();
                $dateFin = Carbon::now()->endOfWeek();
                break;
            case 'mois':
                $dateDebut = Carbon::now()->startOfMonth();
                $dateFin = Carbon::now()->endOfMonth();
                break;
            case 'annee':
                $dateDebut = Carbon::now()->startOfYear();
                $dateFin = Carbon::now()->endOfYear();
                break;
            default: // journée
                $dateDebut = Carbon::today();
                $dateFin = Carbon::today()->endOfDay();
        }

        // Commandes en cours
        $commandesEnCours = Commande::where('etat', 'validee')->count();

        // Commandes à livrer aujourd'hui
        $commandesLivraisonJournee = Commande::whereDate('date_livraison_prevue', Carbon::today())
            ->where('etat', 'validee')
            ->count();

        // Dette totale
        $detteTotale = DB::table('commandes')
            ->select(DB::raw('SUM(montant_total - COALESCE((
                SELECT SUM(montant) 
                FROM versements 
                WHERE commande_id = commandes.id
            ), 0)) as dette'))
            ->whereIn('etat', ['validee', 'recue'])
            ->first()->dette ?? 0;

        // Versements du jour
        $versementsJournee = Versement::whereDate('date_versement', Carbon::today())
            ->sum('montant');

        // Commandes créées dans la période
        $commandesCreees = Commande::whereBetween('date_commande', [$dateDebut, $dateFin])
            ->count();

        // Montant total des commandes dans la période
        $montantCommandes = Commande::whereBetween('date_commande', [$dateDebut, $dateFin])
            ->sum('montant_total');

        // Top fournisseurs par dette
        $topFournisseursDette = Fournisseur::select('fournisseurs.*')
            ->selectSub(function ($query) {
                $query->select(DB::raw('SUM(commandes.montant_total - COALESCE((
                    SELECT SUM(montant) 
                    FROM versements 
                    WHERE commande_id = commandes.id
                ), 0))'))
                ->from('commandes')
                ->whereColumn('commandes.fournisseur_id', 'fournisseurs.id')
                ->whereIn('commandes.etat', ['validee', 'recue']);
            }, 'dette')
            ->having('dette', '>', 0)
            ->orderBy('dette', 'desc')
            ->limit(5)
            ->get();

        // Produits les plus commandés
        $produitsPopulaires = DB::table('commande_produit')
            ->join('produits', 'commande_produit.produit_id', '=', 'produits.id')
            ->select('produits.designation', 'produits.code', 
                DB::raw('SUM(commande_produit.quantite) as quantite_totale'),
                DB::raw('COUNT(DISTINCT commande_produit.commande_id) as nombre_commandes'))
            ->groupBy('produits.id', 'produits.designation', 'produits.code')
            ->orderBy('quantite_totale', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'periode' => $periode,
            'dates' => [
                'debut' => $dateDebut->format('Y-m-d'),
                'fin' => $dateFin->format('Y-m-d')
            ],
            'statistiques_principales' => [
                'commandes_en_cours' => $commandesEnCours,
                'commandes_livraison_journee' => $commandesLivraisonJournee,
                'dette_totale' => $detteTotale,
                'versements_journee' => $versementsJournee,
                'commandes_creees_periode' => $commandesCreees,
                'montant_commandes_periode' => $montantCommandes
            ],
            'repartition_etats' => $this->getRepartitionEtats(),
            'top_fournisseurs_dette' => $topFournisseursDette,
            'produits_populaires' => $produitsPopulaires,
            'evolution_commandes' => $this->getEvolutionCommandes($dateDebut, $dateFin)
        ]);
    }

    private function getRepartitionEtats()
    {
        return [
            'brouillon' => Commande::where('etat', 'brouillon')->count(),
            'validee' => Commande::where('etat', 'validee')->count(),
            'recue' => Commande::where('etat', 'recue')->count(),
            'cloturee' => Commande::where('etat', 'cloturee')->count(),
            'annule' => Commande::where('etat', 'annule')->count(),
        ];
    }

    private function getEvolutionCommandes($dateDebut, $dateFin)
    {
        $jours = collect();
        $dateCourante = $dateDebut->copy();
        
        while ($dateCourante <= $dateFin) {
            $jours->push([
                'date' => $dateCourante->format('Y-m-d'),
                'jour' => $dateCourante->format('d/m'),
                'commandes' => Commande::whereDate('date_commande', $dateCourante)->count(),
                'montant' => Commande::whereDate('date_commande', $dateCourante)->sum('montant_total') ?? 0
            ]);
            
            $dateCourante->addDay();
        }

        return $jours;
    }

    public function detteTotale()
    {
        $dette = DB::table('commandes')
            ->select(DB::raw('SUM(montant_total - COALESCE((
                SELECT SUM(montant) 
                FROM versements 
                WHERE commande_id = commandes.id
            ), 0)) as dette'))
            ->whereIn('etat', ['validee', 'recue'])
            ->first()->dette ?? 0;

        return response()->json([
            'dette_totale' => $dette,
            'dette_formatee' => number_format($dette, 2, ',', ' ') . ' FCFA'
        ]);
    }

    public function versementsJournee()
    {
        $versements = Versement::whereDate('date_versement', Carbon::today())
            ->with('commande.fournisseur')
            ->get();

        $total = $versements->sum('montant');

        return response()->json([
            'date' => Carbon::today()->format('Y-m-d'),
            'nombre_versements' => $versements->count(),
            'total_verse' => $total,
            'versements' => $versements,
            'statistiques' => [
                'moyenne_versement' => $versements->count() > 0 ? $total / $versements->count() : 0,
                'plus_gros_versement' => $versements->max('montant') ?? 0,
                'plus_petit_versement' => $versements->min('montant') ?? 0
            ]
        ]);
    }
}
