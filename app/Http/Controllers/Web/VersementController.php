<?php
// app/Http/Controllers/Web/VersementController.php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Versement;
use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VersementController extends Controller
{
    public function index(Request $request)
    {
        $query = Versement::with(['commande.fournisseur']);
        
        // Filtres
        if ($request->filled('date_debut')) {
            $query->whereDate('date_versement', '>=', $request->date_debut);
        }
        
        if ($request->filled('date_fin')) {
            $query->whereDate('date_versement', '<=', $request->date_fin);
        }
        
        if ($request->filled('commande_id')) {
            $query->where('commande_id', $request->commande_id);
        }
        
        $versements = $query->latest()->get();
        $commandes = Commande::where('etat', Commande::ETAT_RECUE)
            ->whereNotNull('date_livraison_reelle')
            ->with('fournisseur')
            ->get();
        
        // Statistiques
        $today = now()->format('Y-m-d');
        $thisMonth = now()->format('Y-m');
        
        $stats = [
            'versements_jour' => Versement::whereDate('date_versement', $today)->sum('montant'),
            'versements_mois' => Versement::whereRaw("DATE_FORMAT(date_versement, '%Y-%m') = ?", [$thisMonth])
                ->sum('montant'),
            'versements_total' => Versement::sum('montant'),
        ];
        
        return view('versements.index', compact('versements', 'commandes', 'stats'));
    }
    
    public function create()
    {
        $commandes = Commande::where('etat', Commande::ETAT_RECUE)
            ->whereNotNull('date_livraison_reelle')
            ->with('fournisseur')
            ->get()
            ->filter(function($commande) {
                return $commande->montantRestant() > 0;
            });

        if (!view()->exists('versements.create')) {
            return redirect()->route('versements.index');
        }
            
        return view('versements.create', compact('commandes'));
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'commande_id' => 'required|exists:commandes,id',
            'date_versement' => 'required|date',
            'montant' => 'required|numeric|min:0',
        ]);
        
        $commande = Commande::findOrFail($request->commande_id);

        if (!$commande->date_livraison_reelle || $commande->etat !== Commande::ETAT_RECUE) {
            return back()->withErrors([
                'commande_id' => "Le paiement est autorisé uniquement après la livraison réelle."
            ])->withInput();
        }
        
        // Vérifier le nombre de versements (maximum 3 selon le cahier des charges)
        $nombreVersements = $commande->versements()->count();
        if ($nombreVersements >= 3) {
            return back()->withErrors([
                'montant' => "Le nombre maximum de versements (3) est atteint pour cette commande."
            ])->withInput();
        }
        
        // Vérifier que les versements sont égaux
        $montantVerse = $commande->versements()->sum('montant');
        $montantRestant = $commande->montant_total - $montantVerse;
        
        if ($nombreVersements > 0) {
            $montantPremierVersement = $commande->versements()->first()->montant;
            // Autoriser une petite différence due aux arrondis
            if (abs($request->montant - $montantPremierVersement) > 1) {
                return back()->withErrors([
                    'montant' => "Les versements doivent être égaux. Montant attendu: " . 
                                number_format($montantPremierVersement, 0, ',', ' ') . " FCFA"
                ])->withInput();
            }
        }
        
        // Vérifier l'intervalle de 5 jours à partir de la date de livraison réelle
        if ($commande->date_livraison_reelle) {
            $dateLivraisonReelle = \Carbon\Carbon::parse($commande->date_livraison_reelle);
            $dateVersement = \Carbon\Carbon::parse($request->date_versement);
            
            // Le premier versement doit être à 5 jours après la livraison
            $dateMinimale = $dateLivraisonReelle->copy()->addDays(5);
            $dateMaximale = $dateMinimale->copy()->addDays(5 * ($nombreVersements + 1));
            
            if ($dateVersement->lt($dateMinimale)) {
                return back()->withErrors([
                    'date_versement' => "Le premier versement doit être au moins 5 jours après la livraison réelle."
                ])->withInput();
            }
            
            if ($nombreVersements > 0) {
                // Vérifier que chaque versement suivants est à 5 jours d'intervalle
                $dernierVersement = $commande->versements()->latest()->first();
                $dateDernierVersement = \Carbon\Carbon::parse($dernierVersement->date_versement);
                $dateMinimaleSuivant = $dateDernierVersement->copy()->addDays(5);
                
                if ($dateVersement->lt($dateMinimaleSuivant)) {
                    return back()->withErrors([
                        'date_versement' => "Le versement suivant doit être au moins 5 jours après le précédent."
                    ])->withInput();
                }
            }
        }
        
        // Vérifier le montant restant
        if ($request->montant > $montantRestant) {
            return back()->withErrors([
                'montant' => "Le montant ne peut pas dépasser le montant restant: " . 
                            number_format($montantRestant, 0, ',', ' ') . " FCFA"
            ])->withInput();
        }
        
        DB::beginTransaction();
        
        try {
            // Générer le numéro de versement
            $date = \Carbon\Carbon::parse($request->date_versement);
            $count = Versement::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
                
            $numeroVersement = 'VERS-' . $date->format('Ym') . '-' . 
                str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            
            $versement = Versement::create([
                'commande_id' => $request->commande_id,
                'numero_versement' => $numeroVersement,
                'date_versement' => $request->date_versement,
                'montant' => $request->montant
            ]);
            
            // Vérifier si la commande est maintenant totalement payée
            if ($commande->montantRestant() == 0) {
                $commande->update(['etat' => Commande::ETAT_CLOTUREE]);
            }
            
            DB::commit();
            
            return redirect()->route('versements.show', $versement)
                ->with('success', 'Versement enregistré avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur lors de l\'enregistrement du versement: ' . $e->getMessage()])
                ->withInput();
        }
    }
    
    public function show(Versement $versement)
    {
        $versement->load(['commande.fournisseur', 'commande.versements']);

        if (!view()->exists('versements.show')) {
            return redirect()->route('versements.index');
        }
        
        return view('versements.show', compact('versement'));
    }
    
    public function edit(Versement $versement)
    {
        if (!view()->exists('versements.edit')) {
            return redirect()->route('versements.index');
        }

        return view('versements.edit', compact('versement'));
    }
    
    public function update(Request $request, Versement $versement)
    {
        $request->validate([
            'date_versement' => 'required|date',
            'montant' => 'required|numeric|min:0',
        ]);
        
        // Vérifier le nouveau montant par rapport au montant restant de la commande
        $commande = $versement->commande;
        $montantVerseSansCeVersement = $commande->versements()
            ->where('id', '!=', $versement->id)
            ->sum('montant');
        $nouveauMontantRestant = $commande->montant_total - ($montantVerseSansCeVersement + $request->montant);
        
        if ($nouveauMontantRestant < 0) {
            return back()->withErrors([
                'montant' => "Le nouveau montant dépasse le montant total de la commande. " .
                            "Montant maximum: " . number_format($commande->montant_total - $montantVerseSansCeVersement, 0, ',', ' ') . " FCFA"
            ])->withInput();
        }
        
        DB::beginTransaction();
        
        try {
            $versement->update([
                'date_versement' => $request->date_versement,
                'montant' => $request->montant
            ]);
            
            // Recalculer l'état de la commande
            if ($nouveauMontantRestant == 0) {
                $commande->update(['etat' => Commande::ETAT_CLOTUREE]);
            } elseif ($commande->etat === Commande::ETAT_CLOTUREE && $nouveauMontantRestant > 0) {
                $commande->update(['etat' => Commande::ETAT_RECUE]);
            }
            
            DB::commit();
            
            return redirect()->route('versements.show', $versement)
                ->with('success', 'Versement mis à jour avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour du versement: ' . $e->getMessage()])
                ->withInput();
        }
    }
    
    public function destroy(Versement $versement)
    {
        $commande = $versement->commande;
        
        DB::beginTransaction();
        
        try {
            $versement->delete();
            
            // Recalculer l'état de la commande
            $montantRestant = $commande->montantRestant();
            
            if ($montantRestant > 0 && $commande->etat === Commande::ETAT_CLOTUREE) {
                $commande->update(['etat' => Commande::ETAT_RECUE]);
            }
            
            DB::commit();
            
            return redirect()->route('versements.index')
                ->with('success', 'Versement supprimé avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('versements.index')
                ->with('error', 'Erreur lors de la suppression du versement: ' . $e->getMessage());
        }
    }
    
    public function genererEchelonnes(Request $request, Commande $commande)
    {
        if (!$commande->date_livraison_reelle) {
            return redirect()->route('commandes.show', $commande)
                ->with('error', 'La commande doit être livrée pour générer les versements échelonnés.');
        }
        
        $montantRestant = $commande->montantRestant();
        
        if ($montantRestant <= 0) {
            return redirect()->route('commandes.show', $commande)
                ->with('error', 'La commande est déjà totalement payée.');
        }
        
        $nombreVersements = min(3, ceil($montantRestant / 100000)); // Max 3 versements
        $montantVersement = $montantRestant / $nombreVersements;
        
        DB::beginTransaction();
        
        try {
            $dateVersement = \Carbon\Carbon::parse($commande->date_livraison_reelle);
            $versements = [];
            
            for ($i = 1; $i <= $nombreVersements; $i++) {
                $dateVersement->addDays(5);
                
                $count = Versement::whereYear('created_at', $dateVersement->year)
                    ->whereMonth('created_at', $dateVersement->month)
                    ->count();
                    
                $numeroVersement = 'VERS-' . $dateVersement->format('Ym') . '-' . 
                    str_pad($count + 1, 4, '0', STR_PAD_LEFT);
                
                $versement = Versement::create([
                    'commande_id' => $commande->id,
                    'numero_versement' => $numeroVersement,
                    'date_versement' => $dateVersement->format('Y-m-d'),
                    'montant' => $montantVersement
                ]);
                
                $versements[] = $versement;
            }
            
            DB::commit();
            
            return redirect()->route('commandes.show', $commande)
                ->with('success', "$nombreVersements versements échelonnés créés avec succès.");
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('commandes.show', $commande)
                ->with('error', 'Erreur lors de la génération des versements: ' . $e->getMessage());
        }
    }
}
