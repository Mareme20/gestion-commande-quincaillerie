<?php
// app/Http/Controllers/Web/CommandeController.php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Fournisseur;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CommandeController extends Controller
{
    public function index(Request $request)
    {
        $query = Commande::with(['fournisseur', 'produits']);
        
        // Filtres
        if ($request->filled('etat')) {
            $query->where('etat', $request->etat);
        }
        
        if ($request->filled('fournisseur_id')) {
            $query->where('fournisseur_id', $request->fournisseur_id);
        }
        
        if ($request->filled('date_debut')) {
            $query->whereDate('date_commande', '>=', $request->date_debut);
        }
        
        if ($request->filled('date_fin')) {
            $query->whereDate('date_commande', '<=', $request->date_fin);
        }
        
        $commandes = $query->latest()->get();
        $fournisseurs = Fournisseur::where('fournisseurs.archive', false)->get();
        
        // Statistiques par état
        $stats = [
            'en_cours' => Commande::where('etat', 'en_cours')->count(),
            'livre' => Commande::where('etat', 'livre')->count(),
            'paye' => Commande::where('etat', 'paye')->count(),
            'annule' => Commande::where('etat', 'annule')->count(),
        ];
        
        return view('commandes.index', compact('commandes', 'fournisseurs', 'stats'));
    }
    
    public function create()
    {
        $fournisseurs = Fournisseur::where('fournisseurs.archive', false)->get();
        $produits = Produit::where('produits.archive', false)
            ->whereNotNull('fournisseur_id')
            ->with(['sousCategorie.categorie', 'fournisseur'])
            ->get();
            
        return view('commandes.create', compact('fournisseurs', 'produits'));
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'date_commande' => 'required|date',
            'date_livraison_prevue' => 'required|date|after_or_equal:date_commande',
            'produits' => 'required|array|min:1',
            'produits.*.id' => 'required|exists:produits,id',
            'produits.*.quantite' => 'required|numeric|min:0.01',
            'produits.*.prix_achat' => 'required|numeric|min:0',
            'montant_total' => 'required|numeric|min:0',
        ]);
        
        DB::beginTransaction();
        
        try {
            $montantTotal = 0;
            $produitsData = [];
            
            foreach ($request->produits as $index => $produit) {
                $montantTotal += $produit['quantite'] * $produit['prix_achat'];
                
                $produitsData[$produit['id']] = [
                    'quantite' => $produit['quantite'],
                    'prix_achat' => $produit['prix_achat'],
                    'fournisseur_id' => $request->fournisseur_id
                ];
            }

            $produitIds = collect($request->produits)
                ->pluck('id')
                ->unique()
                ->values();
            $produitsCommande = Produit::whereIn('id', $produitIds)->get(['id', 'fournisseur_id', 'designation']);

            $produitsIncompatibles = $produitsCommande->filter(function ($produit) use ($request) {
                return !$produit->fournisseur_id || (int) $produit->fournisseur_id !== (int) $request->fournisseur_id;
            });

            if ($produitsIncompatibles->isNotEmpty()) {
                $noms = $produitsIncompatibles->pluck('designation')->implode(', ');
                return back()->withErrors([
                    'produits' => "Ces produits ne sont pas rattachés à ce fournisseur: {$noms}."
                ])->withInput();
            }
            
            // Vérifier que le montant total correspond
            if (abs($montantTotal - $request->montant_total) > 0.01) {
                return back()->withErrors(['montant_total' => 'Le montant total ne correspond pas aux produits.'])
                    ->withInput();
            }
            
            $commande = Commande::create([
                'fournisseur_id' => $request->fournisseur_id,
                'date_commande' => $request->date_commande,
                'montant_total' => $montantTotal,
                'date_livraison_prevue' => $request->date_livraison_prevue,
                'etat' => 'en_cours'
            ]);
            
            $commande->produits()->attach($produitsData);
            
            DB::commit();
            
            return redirect()->route('commandes.show', $commande)
                ->with('success', 'Commande créée avec succès.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur lors de la création de la commande: ' . $e->getMessage()])
                ->withInput();
        }
    }
    
    public function show(Commande $commande)
    {
        $commande->load(['fournisseur', 'produits.sousCategorie.categorie', 'versements']);
        
        $montantVerse = $commande->versements->sum('montant');
        $montantRestant = $commande->montant_total - $montantVerse;
        $pourcentagePaye = $commande->montant_total > 0 ? 
            ($montantVerse / $commande->montant_total) * 100 : 0;
        
        return view('commandes.show', compact('commande', 'montantVerse', 'montantRestant', 'pourcentagePaye'));
    }
    
    public function edit(Commande $commande)
    {
        if ($commande->etat !== 'en_cours') {
            return redirect()->route('commandes.show', $commande)
                ->with('error', 'Seules les commandes en cours peuvent être modifiées.');
        }
        
        $commande->load(['produits']);
        $fournisseurs = Fournisseur::where('fournisseurs.archive', false)->get();

        if (!view()->exists('commandes.edit')) {
            return redirect()->route('commandes.show', $commande)
                ->with('info', 'La modification se fait depuis la fiche commande.');
        }
        
        return view('commandes.edit', compact('commande', 'fournisseurs'));
    }
    
    public function update(Request $request, Commande $commande)
    {
        if ($commande->etat !== 'en_cours') {
            return redirect()->route('commandes.show', $commande)
                ->with('error', 'Seules les commandes en cours peuvent être modifiées.');
        }
        
        $request->validate([
            'date_livraison_prevue' => 'sometimes|required|date',
            'date_livraison_reelle' => 'nullable|date',
            'etat' => 'sometimes|in:en_cours,livre,paye,annule'
        ]);
        
        $commande->update($request->only(['date_livraison_prevue', 'date_livraison_reelle', 'etat']));
        
        return redirect()->route('commandes.show', $commande)
            ->with('success', 'Commande mise à jour avec succès.');
    }
    
    public function annuler(Commande $commande)
    {
        if ($commande->etat !== 'en_cours') {
            return redirect()->route('commandes.show', $commande)
                ->with('error', 'Seules les commandes en cours peuvent être annulées.');
        }
        
        $commande->update(['etat' => 'annule']);
        
        return redirect()->route('commandes.index')
            ->with('success', 'Commande annulée avec succès.');
    }
    
    public function destroy(Commande $commande)
    {
        if ($commande->etat !== 'en_cours') {
            return redirect()->route('commandes.show', $commande)
                ->with('error', 'Seules les commandes en cours peuvent être supprimées.');
        }
        
        $commande->delete();
        
        return redirect()->route('commandes.index')
            ->with('success', 'Commande supprimée avec succès.');
    }
}
