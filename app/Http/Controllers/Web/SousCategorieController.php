<?php
// app/Http\Controllers/Web/SousCategorieController.php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SousCategorie;
use App\Models\Categorie;
use Illuminate\Http\Request;

class SousCategorieController extends Controller
{
    /**
     * Afficher les sous-catégories d'une catégorie
     */
    public function index(Request $request)
    {
        $query = SousCategorie::with('categorie');
        
        if ($request->filled('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }
        
        if ($request->filled('archive')) {
            $query->where('sous_categories.archive', $request->archive);
        }
        
        $sousCategories = $query->latest()->get();
        $categories = Categorie::active()->get();
        
        return view('sous-categories.index', compact('sousCategories', 'categories'));
    }
    
    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        $categories = Categorie::active()->get();
        return view('sous-categories.create', compact('categories'));
    }
    
    /**
     * Stocker une nouvelle sous-catégorie
     */
    public function store(Request $request)
    {
        $request->validate([
            'categorie_id' => 'required|exists:categories,id',
            'nom' => 'required|string|max:255|unique:sous_categories,nom,NULL,id,categorie_id,' . $request->categorie_id,
            'description' => 'nullable|string',
        ]);
        
        SousCategorie::create($request->all());
        
        return redirect()->route('categories.show', $request->categorie_id)
            ->with('success', 'Sous-catégorie créée avec succès.');
    }
    
    /**
     * Afficher une sous-catégorie
     */
    public function show(SousCategorie $sousCategorie)
    {
        $sousCategorie->load(['categorie', 'produits' => function($query) {
            $query->where('produits.archive', false)->latest();
        }]);

        if (!view()->exists('sous-categories.show')) {
            return redirect()->route('categories.show', $sousCategorie->categorie_id);
        }
        
        return view('sous-categories.show', compact('sousCategorie'));
    }
    
    /**
     * Afficher le formulaire d'édition
     */
    public function edit(SousCategorie $sousCategorie)
    {
        $categories = Categorie::active()->get();

        if (!view()->exists('sous-categories.edit')) {
            return redirect()->route('categories.show', $sousCategorie->categorie_id);
        }

        return view('sous-categories.edit', compact('sousCategorie', 'categories'));
    }
    
    /**
     * Mettre à jour une sous-catégorie
     */
    public function update(Request $request, SousCategorie $sousCategorie)
    {
        $request->validate([
            'categorie_id' => 'required|exists:categories,id',
            'nom' => 'required|string|max:255|unique:sous_categories,nom,' . $sousCategorie->id . ',id,categorie_id,' . $request->categorie_id,
            'description' => 'nullable|string',
        ]);
        
        $sousCategorie->update($request->all());
        
        return redirect()->route('sous-categories.show', $sousCategorie)
            ->with('success', 'Sous-catégorie mise à jour avec succès.');
    }
    
    /**
     * Archiver une sous-catégorie
     */
    public function archive(SousCategorie $sousCategorie)
    {
        // Vérifier si la sous-catégorie a des produits actifs
        $produitsActifs = $sousCategorie->produits()
            ->where('produits.archive', false)
            ->count();
            
        if ($produitsActifs > 0) {
            return back()->with('error', 'Impossible d\'archiver cette sous-catégorie car elle contient des produits actifs.');
        }
        
        $sousCategorie->update(['archive' => !$sousCategorie->archive]);
        
        $message = $sousCategorie->archive ? 'archivée' : 'désarchivée';
        
        return back()->with('success', "Sous-catégorie {$message} avec succès.");
    }
    
    /**
     * Supprimer une sous-catégorie
     */
    public function destroy(SousCategorie $sousCategorie)
    {
        // Vérifier si la sous-catégorie a des produits
        if ($sousCategorie->tousProduits()->count() > 0) {
            return redirect()->route('categories.show', $sousCategorie->categorie_id)
                ->with('error', 'Impossible de supprimer cette sous-catégorie car elle contient des produits.');
        }
        
        $categorieId = $sousCategorie->categorie_id;
        $sousCategorie->delete();
        
        return redirect()->route('categories.show', $categorieId)
            ->with('success', 'Sous-catégorie supprimée avec succès.');
    }
}
