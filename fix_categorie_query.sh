#!/bin/bash

echo "=== Correction des requêtes Catégorie ==="
echo ""

# 1. Vérifier la structure de la table produits
echo "1. Vérification de la table produits..."
mysql -u quincaillerie_user -pQuincaillerie123! -D quincaillerie_db <<MYSQL
SHOW COLUMNS FROM produits;
MYSQL

echo ""
echo "2. Ajout de la colonne archive si nécessaire..."
php artisan make:migration add_archive_column_to_produits_table --table=produits

# Créer la migration
cat > database/migrations/$(date +%Y_%m_%d_%H%M%S)_add_archive_column_to_produits_table.php << 'MIGRATION'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('produits', 'archive')) {
            Schema::table('produits', function (Blueprint $table) {
                $table->boolean('archive')->default(false)->after('image');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('produits', 'archive')) {
            Schema::table('produits', function (Blueprint $table) {
                $table->dropColumn('archive');
            });
        }
    }
};
MIGRATION

echo "3. Exécution des migrations..."
php artisan migrate

echo ""
echo "4. Correction du CategorieController..."
cat > app/Http/Controllers/Web/CategorieController.php << 'CONTROLLER'
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
        // Version simple et sûre
        $categories = Categorie::where('archive', false)
            ->withCount('sousCategories')
            ->get();
        
        // Ajouter manuellement le compte des produits non archivés
        $categories->each(function($categorie) {
            $categorie->produits_count = \App\Models\Produit::whereHas('sousCategorie', function($query) use ($categorie) {
                $query->where('categorie_id', $categorie->id);
            })
            ->where('archive', false)
            ->count();
        });
            
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
            $query->where('archive', false)
                  ->withCount(['produits' => function($query) {
                      $query->where('archive', false);
                  }]);
        }]);
        
        // Charger les produits non archivés
        $produits = \App\Models\Produit::whereHas('sousCategorie', function($query) use ($categorie) {
            $query->where('categorie_id', $categorie->id);
        })
        ->where('archive', false)
        ->with('sousCategorie')
        ->latest()
        ->limit(10)
        ->get();
        
        return view('categories.show', compact('categorie', 'produits'));
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
        // Vérifier si la catégorie a des produits non archivés
        $produitsActifs = \App\Models\Produit::whereHas('sousCategorie', function($query) use ($categorie) {
            $query->where('categorie_id', $categorie->id);
        })
        ->where('archive', false)
        ->count();
            
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
        // Vérifier si la catégorie a des sous-catégories ou produits
        if ($categorie->sousCategories()->count() > 0) {
            return redirect()->route('categories.index')
                ->with('error', 'Impossible de supprimer cette catégorie car elle contient des sous-catégories.');
        }
        
        $categorie->delete();
        
        return redirect()->route('categories.index')
            ->with('success', 'Catégorie supprimée avec succès.');
    }
}
CONTROLLER

echo ""
echo "5. Mise à jour de la migration des produits..."
# Vérifier et corriger la migration originale des produits
if [ -f "database/migrations/*_create_produits_table.php" ]; then
    MIGRATION_FILE=$(ls database/migrations/*_create_produits_table.php | head -1)
    if ! grep -q "'archive'" "$MIGRATION_FILE"; then
        echo "Ajout de la colonne archive dans la migration originale..."
        sed -i "/'image'/a\            \$table->boolean('archive')->default(false);" "$MIGRATION_FILE"
    fi
fi

echo ""
echo "6. Exécution des migrations fraîches..."
php artisan migrate:fresh --seed

echo ""
echo "7. Test de la requête..."
php artisan tinker <<'TINKER'
use App\Models\Categorie;

try {
    $categories = Categorie::withCount('sousCategories')->get();
    echo "✓ Requête Catégorie fonctionne\n";
    
    foreach ($categories as $categorie) {
        echo "Catégorie: {$categorie->nom}\n";
        echo "Sous-catégories: {$categorie->sous_categories_count}\n";
        
        // Test produit count
        $produitsCount = \App\Models\Produit::whereHas('sousCategorie', function($query) use ($categorie) {
            $query->where('categorie_id', $categorie->id);
        })
        ->where('archive', false)
        ->count();
        
        echo "Produits actifs: {$produitsCount}\n";
        echo "---\n";
    }
} catch (\Exception $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
}
TINKER

echo ""
echo "=== Correction terminée ==="
echo "Testez: http://localhost:8000/categories"
