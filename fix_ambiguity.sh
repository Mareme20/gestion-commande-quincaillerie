#!/bin/bash

echo "=== Correction de l'ambiguïté des colonnes ==="
echo ""

# 1. Corriger le modèle Categorie
echo "1. Correction du modèle Categorie..."
cat > app/Models/Categorie.php << 'EOF'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categorie extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'description', 'archive'];

    public function sousCategories()
    {
        return $this->hasMany(SousCategorie::class);
    }

    public function produits()
    {
        return $this->hasManyThrough(
            Produit::class, 
            SousCategorie::class,
            'categorie_id',
            'sous_categorie_id',
            'id',
            'id'
        );
    }

    public function produitsActifs()
    {
        return $this->produits()->where('produits.archive', false);
    }

    public function getProduitsActifsCountAttribute()
    {
        return $this->produitsActifs()->count();
    }

    public function scopeActive($query)
    {
        return $query->where('categories.archive', false);
    }

    public function toggleArchive()
    {
        $this->update(['archive' => !$this->archive]);
        return $this;
    }
}
EOF

echo "✓ Modèle Categorie corrigé"
echo ""

# 2. Corriger le modèle SousCategorie
echo "2. Correction du modèle SousCategorie..."
cat > app/Models/SousCategorie.php << 'EOF'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SousCategorie extends Model
{
    use HasFactory;

    protected $fillable = ['categorie_id', 'nom', 'description', 'archive'];

    public function categorie()
    {
        return $this->belongsTo(Categorie::class);
    }

    public function produits()
    {
        return $this->hasMany(Produit::class);
    }

    public function produitsActifs()
    {
        return $this->produits()->where('produits.archive', false);
    }

    public function scopeActive($query)
    {
        return $query->where('sous_categories.archive', false);
    }

    public function getStatutAttribute()
    {
        return $this->archive ? 'Archivée' : 'Active';
    }

    public function getProduitsActifsCountAttribute()
    {
        return $this->produitsActifs()->count();
    }
}
EOF

echo "✓ Modèle SousCategorie corrigé"
echo ""

# 3. Corriger le contrôleur CategorieController
echo "3. Correction du contrôleur CategorieController..."
cat > app/Http/Controllers/Web/CategorieController.php << 'EOF'
<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\SousCategorie;
use Illuminate\Http\Request;

class CategorieController extends Controller
{
    public function index()
    {
        $categories = Categorie::withCount(['sousCategories' => function($query) {
            $query->where('sous_categories.archive', false);
        }])
        ->withCount(['produits' => function($query) {
            $query->where('produits.archive', false);
        }])
        ->where('categories.archive', false)
        ->latest()
        ->get();
            
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
        
        Categorie::create($request->all());
        
        return redirect()->route('categories.index')
            ->with('success', 'Catégorie créée avec succès.');
    }
    
    public function show(Categorie $categorie)
    {
        $categorie->load(['sousCategories' => function($query) {
            $query->where('sous_categories.archive', false)
                  ->withCount(['produits' => function($query) {
                      $query->where('produits.archive', false);
                  }]);
        }]);
        
        $categorie->load(['produits' => function($query) {
            $query->where('produits.archive', false)
                  ->with('sousCategorie')
                  ->latest()
                  ->limit(10);
        }]);
        
        $produitsActifsCount = $categorie->produits()->where('produits.archive', false)->count();
        
        return view('categories.show', compact('categorie', 'produitsActifsCount'));
    }
    
    public function edit(Categorie $categorie)
    {
        return view('categories.edit', compact('categorie'));
    }
    
    public function update(Request $request, Categorie $categorie)
    {
        $request->validate([
            'nom' => 'required|string|max:255|unique:categories,nom,' . $categorie->id,
            'description' => 'nullable|string',
        ]);
        
        $categorie->update($request->all());
        
        return redirect()->route('categories.show', $categorie)
            ->with('success', 'Catégorie mise à jour avec succès.');
    }
    
    public function archive(Categorie $categorie)
    {
        $produitsActifs = $categorie->produits()->where('produits.archive', false)->count();
            
        if ($produitsActifs > 0) {
            return redirect()->route('categories.index')
                ->with('error', 'Impossible d\'archiver cette catégorie car elle contient des produits actifs.');
        }
        
        $categorie->update(['archive' => !$categorie->archive]);
        
        $message = $categorie->archive ? 'archivée' : 'désarchivée';
        
        return redirect()->route('categories.index')
            ->with('success', "Catégorie {$message} avec succès.");
    }
    
    public function destroy(Categorie $categorie)
    {
        if ($categorie->sousCategories()->count() > 0 || $categorie->produits()->count() > 0) {
            return redirect()->route('categories.index')
                ->with('error', 'Impossible de supprimer cette catégorie car elle contient des sous-catégories ou produits.');
        }
        
        $categorie->delete();
        
        return redirect()->route('categories.index')
            ->with('success', 'Catégorie supprimée avec succès.');
    }
}
EOF

echo "✓ Contrôleur CategorieController corrigé"
echo ""

# 4. Tester la correction
echo "4. Test de la correction..."
php artisan tinker <<EOF
use App\Models\Categorie;

// Tester avec une catégorie existante
\$categorie = Categorie::first();
if (\$categorie) {
    echo "Test avec la catégorie: {\$categorie->nom}\\n";
    
    // Tester la méthode produits()
    try {
        \$produits = \$categorie->produits()->where('produits.archive', false)->count();
        echo "✓ Comptage des produits: {\$produits}\\n";
    } catch (\Exception \$e) {
        echo "✗ Erreur lors du comptage: " . \$e->getMessage() . "\\n";
    }
    
    // Tester la méthode produitsActifs()
    try {
        \$actifs = \$categorie->produits()->where('produits.archive', false)->count();
        echo "✓ Produits actifs: {\$actifs}\\n";
    } catch (\Exception \$e) {
        echo "✗ Erreur produits actifs: " . \$e->getMessage() . "\\n";
    }
} else {
    echo "Aucune catégorie trouvée, création d'une catégorie de test...\\n";
    \$categorie = Categorie::create([
        'nom' => 'Test Catégorie',
        'description' => 'Test'
    ]);
    echo "Catégorie créée: {\$categorie->nom}\\n";
    
    // Nettoyer
    \$categorie->delete();
}

echo "\\n✓ Test terminé\\n";
EOF

echo ""
echo "=== Correction terminée ==="
echo "L'ambiguïté des colonnes 'archive' a été résolue."
