<?php
// app/Http/Controllers/Web/ProduitController.php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use App\Models\Categorie;
use App\Models\SousCategorie;
use App\Models\Fournisseur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProduitController extends Controller
{
    public function index(Request $request)
    {
        $query = Produit::with(['sousCategorie.categorie', 'fournisseur'])
            ->where('produits.archive', false);
        
        // Filtres
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhere('designation', 'LIKE', "%{$search}%");
            });
        }
        
        if ($request->filled('categorie_id')) {
            $categorieId = $request->categorie_id;
            $query->whereHas('sousCategorie', function($q) use ($categorieId) {
                $q->where('categorie_id', $categorieId);
            });
        }
        
        if ($request->filled('stock_min')) {
            $query->where('quantite_stock', '>=', $request->stock_min);
        }
        
        $produits = $query->latest()->get();
        $categories = Categorie::where('categories.archive', false)->get();
        $fournisseurs = Fournisseur::where('fournisseurs.archive', false)->get();
        
        return view('produits.index', compact('produits', 'categories', 'fournisseurs'));
    }
    
    public function create()
    {
        $categories = Categorie::where('categories.archive', false)->get();
        $fournisseurs = Fournisseur::where('fournisseurs.archive', false)->get();

        return view('produits.create', compact('categories', 'fournisseurs'));
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'sous_categorie_id' => 'required|exists:sous_categories,id',
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'code' => 'required|string|unique:produits,code|max:50',
            'designation' => 'required|string|max:255',
            'quantite_stock' => 'required|numeric|min:0',
            'prix_unitaire' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
        ]);
        
        $data = $request->all();
        
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('produits', 'public');
            $data['image'] = $path;
        }
        
        Produit::create($data);
        
        return redirect()->route('produits.index')
            ->with('success', 'Produit créé avec succès.');
    }
    
    public function show(Produit $produit)
    {
        $produit->load(['sousCategorie.categorie', 'fournisseur', 'commandes.fournisseur']);
        
        // Statistiques du produit
        $totalVendu = $produit->commandes->sum(function($commande) use ($produit) {
            $pivot = $commande->pivot;
            return $pivot->quantite;
        });
        
        $chiffreAffaires = $produit->commandes->sum(function($commande) use ($produit) {
            $pivot = $commande->pivot;
            return $pivot->quantite * $pivot->prix_achat;
        });
        
        return view('produits.show', compact('produit', 'totalVendu', 'chiffreAffaires'));
    }
    
    public function edit(Produit $produit)
    {
        $produit->load(['sousCategorie.categorie']);
        $categories = Categorie::where('categories.archive', false)->get();
        $sousCategories = SousCategorie::where('categorie_id', $produit->sousCategorie->categorie_id)
            ->where('sous_categories.archive', false)
            ->get();

        if (!view()->exists('produits.edit')) {
            return redirect()->route('produits.show', $produit)
                ->with('info', 'La modification du produit se fait depuis la fiche produit.');
        }

        return view('produits.edit', compact('produit', 'categories', 'sousCategories'));
    }
    
    public function update(Request $request, Produit $produit)
    {
        $request->validate([
            'sous_categorie_id' => 'required|exists:sous_categories,id',
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'code' => 'required|string|max:50|unique:produits,code,' . $produit->id,
            'designation' => 'required|string|max:255',
            'quantite_stock' => 'required|numeric|min:0',
            'prix_unitaire' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
        ]);
        
        $data = $request->all();
        
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image
            if ($produit->image) {
                Storage::disk('public')->delete($produit->image);
            }
            
            $path = $request->file('image')->store('produits', 'public');
            $data['image'] = $path;
        }
        
        $produit->update($data);
        
        return redirect()->route('produits.show', $produit)
            ->with('success', 'Produit mis à jour avec succès.');
    }
    
    public function archive(Produit $produit)
    {
        $produit->update(['archive' => !$produit->archive]);
        
        $message = $produit->archive ? 'archivé' : 'désarchivé';
        
        return redirect()->route('produits.index')
            ->with('success', "Produit {$message} avec succès.");
    }
    
    public function destroy(Produit $produit)
    {
        // Vérifier si le produit est utilisé dans des commandes
        if ($produit->commandes()->count() > 0) {
            return redirect()->route('produits.index')
                ->with('error', 'Impossible de supprimer ce produit car il est utilisé dans des commandes.');
        }
        
        // Supprimer l'image
        if ($produit->image) {
            Storage::disk('public')->delete($produit->image);
        }
        
        $produit->delete();
        
        return redirect()->route('produits.index')
            ->with('success', 'Produit supprimé avec succès.');
    }
    
    public function uploadImage(Request $request, Produit $produit)
    {
        $request->validate([
            'image' => 'required|image|max:2048',
        ]);
        
        // Supprimer l'ancienne image
        if ($produit->image) {
            Storage::disk('public')->delete($produit->image);
        }
        
        $path = $request->file('image')->store('produits', 'public');
        $produit->update(['image' => $path]);
        
        return back()->with('success', 'Image téléchargée avec succès.');
    }
}
