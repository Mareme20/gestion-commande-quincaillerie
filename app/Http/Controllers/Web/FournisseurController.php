<?php
// app/Http/Controllers/Web/FournisseurController.php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Fournisseur;
use Illuminate\Http\Request;

class FournisseurController extends Controller
{
    public function index()
    {
        $fournisseurs = Fournisseur::where('fournisseurs.archive', false)
            ->withCount('commandes')
            ->get();
            
        // Calculer la dette pour chaque fournisseur
        $fournisseurs->each(function($fournisseur) {
            $fournisseur->dette = $fournisseur->detteTotale();
        });
        
        return view('fournisseurs.index', compact('fournisseurs'));
    }
    
    public function create()
    {
        if (!view()->exists('fournisseurs.create')) {
            return redirect()->route('fournisseurs.index');
        }

        return view('fournisseurs.create');
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'numero' => 'required|string|unique:fournisseurs,numero|max:50',
            'nom' => 'required|string|max:255',
            'adresse' => 'required|string',
        ]);
        
        Fournisseur::create($request->all());
        
        return redirect()->route('fournisseurs.index')
            ->with('success', 'Fournisseur créé avec succès.');
    }
    
    public function show(Fournisseur $fournisseur)
    {
        $fournisseur->load(['commandes' => function($query) {
            $query->latest()->with('versements');
        }, 'commandes.produits']);
        
        // Statistiques
        $commandesEnCours = $fournisseur->commandes->where('etat', 'en_cours')->count();
        $commandesLivrees = $fournisseur->commandes->where('etat', 'livre')->count();
        $commandesPayees = $fournisseur->commandes->where('etat', 'paye')->count();
        $detteTotale = $fournisseur->detteTotale();
        
        // Commandes avec dette
        $commandesAvecDette = $fournisseur->commandes->filter(function($commande) {
            return $commande->etat !== 'paye' && $commande->etat !== 'annule' && 
                   $commande->montantRestant() > 0;
        });
        
        return view('fournisseurs.show', compact(
            'fournisseur', 
            'commandesEnCours',
            'commandesLivrees',
            'commandesPayees',
            'detteTotale',
            'commandesAvecDette'
        ));
    }
    
    public function edit(Fournisseur $fournisseur)
    {
        if (!view()->exists('fournisseurs.edit')) {
            return redirect()->route('fournisseurs.show', $fournisseur);
        }

        return view('fournisseurs.edit', compact('fournisseur'));
    }
    
    public function update(Request $request, Fournisseur $fournisseur)
    {
        $request->validate([
            'numero' => 'required|string|max:50|unique:fournisseurs,numero,' . $fournisseur->id,
            'nom' => 'required|string|max:255',
            'adresse' => 'required|string',
        ]);
        
        $fournisseur->update($request->all());
        
        return redirect()->route('fournisseurs.show', $fournisseur)
            ->with('success', 'Fournisseur mis à jour avec succès.');
    }
    
    public function archive(Fournisseur $fournisseur)
    {
        // Vérifier si le fournisseur a des commandes en cours
        $commandesEnCours = $fournisseur->commandes()
            ->where('etat', 'en_cours')
            ->count();
            
        if ($commandesEnCours > 0) {
            return redirect()->route('fournisseurs.index')
                ->with('error', 'Impossible d\'archiver ce fournisseur car il a des commandes en cours.');
        }
        
        $fournisseur->update(['archive' => !$fournisseur->archive]);
        
        $message = $fournisseur->archive ? 'archivé' : 'désarchivé';
        
        return redirect()->route('fournisseurs.index')
            ->with('success', "Fournisseur {$message} avec succès.");
    }
    
    public function destroy(Fournisseur $fournisseur)
    {
        // Vérifier si le fournisseur a des commandes
        if ($fournisseur->commandes()->count() > 0) {
            return redirect()->route('fournisseurs.index')
                ->with('error', 'Impossible de supprimer ce fournisseur car il a des commandes associées.');
        }
        
        $fournisseur->delete();
        
        return redirect()->route('fournisseurs.index')
            ->with('success', 'Fournisseur supprimé avec succès.');
    }
    
    public function dette(Fournisseur $fournisseur)
    {
        $dette = $fournisseur->detteTotale();
        $commandesAvecDette = $fournisseur->commandes()
            ->whereIn('etat', ['en_cours', 'livre'])
            ->with('versements')
            ->get()
            ->filter(function($commande) {
                return $commande->montantRestant() > 0;
            });
        
        if (!view()->exists('fournisseurs.dette')) {
            return redirect()->route('fournisseurs.show', $fournisseur);
        }

        return view('fournisseurs.dette', compact('fournisseur', 'dette', 'commandesAvecDette'));
    }
}
