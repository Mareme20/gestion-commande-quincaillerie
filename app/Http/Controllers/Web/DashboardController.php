<?php
// app/Http/Controllers/Web/DashboardController.php

namespace App\Http\Controllers\Web;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Versement;
use App\Models\Fournisseur;
use App\Models\Produit;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        // Statistiques principales
        $commandesEnCours = Commande::where('etat', 'en_cours')->count();
        
        $commandesLivraisonJournee = Commande::whereDate('date_livraison_prevue', Carbon::today())
            ->where('etat', 'en_cours')
            ->count();
        
        // Dette totale
        $detteTotale = DB::table('commandes')
            ->select(DB::raw('SUM(montant_total - COALESCE((
                SELECT SUM(montant) 
                FROM versements 
                WHERE commande_id = commandes.id
            ), 0)) as dette'))
            ->whereIn('etat', ['en_cours', 'livre'])
            ->first()->dette ?? 0;
        
        // Versements du jour
        $versementsJournee = Versement::whereDate('date_versement', Carbon::today())
            ->sum('montant');
        
        // Commandes récentes
        $commandesRecentes = Commande::with(['fournisseur', 'produits'])
            ->latest()
            ->limit(5)
            ->get();
        
        // Versements récents
        $versementsRecents = Versement::with(['commande.fournisseur'])
            ->latest()
            ->limit(5)
            ->get();
        
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
                ->whereIn('commandes.etat', ['en_cours', 'livre']);
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
            ->limit(5)
            ->get();
        
        // Évolution des commandes (7 derniers jours)
        $evolutionCommandes = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            
            $totalCommandes = Commande::whereDate('date_commande', $date)->count();
            $totalMontant = Commande::whereDate('date_commande', $date)->sum('montant_total');
            
            $evolutionCommandes[] = [
                'date' => $date->format('Y-m-d'),
                'jour' => $date->format('d/m'),
                'commandes' => $totalCommandes,
                'montant' => $totalMontant
            ];
        }
        
        // Répartition par état
        $repartitionEtats = [
            'en_cours' => Commande::where('etat', 'en_cours')->count(),
            'livre' => Commande::where('etat', 'livre')->count(),
            'paye' => Commande::where('etat', 'paye')->count(),
            'annule' => Commande::where('etat', 'annule')->count(),
        ];
        
        return view('dashboard.index', compact(
            'commandesEnCours',
            'commandesLivraisonJournee',
            'detteTotale',
            'versementsJournee',
            'commandesRecentes',
            'versementsRecents',
            'topFournisseursDette',
            'produitsPopulaires',
            'evolutionCommandes',
            'repartitionEtats'
        ));
    }
    
    public function apiStatistiques(Request $request)
    {
        // Cette méthode peut être utilisée pour les appels AJAX
        $periode = request()->input('periode', 'journee');
        
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
        
        $commandesEnCours = Commande::where('etat', 'en_cours')->count();
        
        $commandesLivraisonJournee = Commande::whereDate('date_livraison_prevue', Carbon::today())
            ->where('etat', 'en_cours')
            ->count();
        
        $detteTotale = DB::table('commandes')
            ->select(DB::raw('SUM(montant_total - COALESCE((
                SELECT SUM(montant) 
                FROM versements 
                WHERE commande_id = commandes.id
            ), 0)) as dette'))
            ->whereIn('etat', ['en_cours', 'livre'])
            ->first()->dette ?? 0;
        
        $versementsJournee = Versement::whereDate('date_versement', Carbon::today())
            ->sum('montant');
        
        // Évolution des commandes pour la période
        $evolutionCommandes = [];
        $currentDate = $dateDebut->copy();
        
        while ($currentDate <= $dateFin) {
            $totalCommandes = Commande::whereDate('date_commande', $currentDate)->count();
            $totalMontant = Commande::whereDate('date_commande', $currentDate)->sum('montant_total');
            
            $evolutionCommandes[] = [
                'date' => $currentDate->format('Y-m-d'),
                'jour' => $currentDate->format('d/m'),
                'commandes' => $totalCommandes,
                'montant' => $totalMontant
            ];
            
            $currentDate->addDay();
        }
        
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
                'versements_journee' => $versementsJournee
            ],
            'evolution_commandes' => $evolutionCommandes
        ]);
    }
}