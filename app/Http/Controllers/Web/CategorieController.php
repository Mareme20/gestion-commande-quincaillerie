<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Produit;
use Illuminate\Http\Request;

class CategorieController extends Controller
{
    public function index()
    {
        // Solution simple et efficace - utiliser les relations avec specification de table
        $categories = Categorie::where('categories.archive', false)
            ->withCount(['sousCategories' => function($query) {
                $query->where('sous_categories.archive', false);
            }])
            ->get();
        
        // Ajouter manuellement le compte des produits non archivés
        foreach ($categories as $categorie) {
            $categorie->produits_count = \App\Models\Produit::whereHas('sousCategorie', function($query) use ($categorie) {
                $query->where('categorie_id', $categorie->id)
                      ->where('sous_categories.archive', false);
            })
            ->where('produits.archive', false)
            ->count();
        }
            
        return view('categories.index', compact('categories'));
    }
    
    public function create()
    {
        return view('categories.create');
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255|unique:categories,nom',
            'description' => 'nullable|string',
        ]);
        
        Categorie::create($request->only(['nom', 'description']));
        
        return redirect()->route('categories.index')
            ->with('success', 'Catégorie créée avec succès.');
    }
    
    public function show(Categorie $category)
    {
        $category->load(['sousCategories' => function($query) {
            $query->where('sous_categories.archive', false)
                ->withCount(['produits' => function($query) {
                    $query->where('produits.archive', false);
                }]);
        }]);

        $category->load(['produits' => function($query) {
            $query->where('produits.archive', false)
                ->with('sousCategorie')
                ->latest()
                ->limit(10);
        }]);

        $produitsActifsCount = $category->produits()->where('produits.archive', false)->count();
        $categorie = $category;

        return view('categories.show', compact('categorie', 'produitsActifsCount'));
    }

    public function edit(Categorie $category)
    {
        return redirect()->route('categories.show', $category)
            ->with('info', 'La modification de catégorie se fait depuis la page détail.');
    }

    public function update(Request $request, Categorie $category)
    {
        $request->validate([
            'nom' => 'required|string|max:255|unique:categories,nom,' . $category->id,
            'description' => 'nullable|string',
        ]);

        $category->update($request->only(['nom', 'description']));

        return redirect()->route('categories.show', $category)
            ->with('success', 'Catégorie mise à jour avec succès.');
    }
    
    public function destroy(Categorie $category)
    {
        if ($category->sousCategories()->count() > 0) {
            return redirect()->route('categories.index')
                ->with('error', 'Impossible de supprimer cette catégorie car elle contient des sous-catégories.');
        }

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Catégorie supprimée avec succès.');
    }

    public function archive(Categorie $category)
    {
        $category->update(['archive' => !$category->archive]);

        $message = $category->archive ? 'archivée' : 'désarchivée';

        return redirect()->route('categories.index')
            ->with('success', "Catégorie {$message} avec succès.");
    }
}
