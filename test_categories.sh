#!/bin/bash

echo "=== TEST DES CATÉGORIES ==="
echo ""

# 1. Démarrer le serveur en arrière-plan
echo "1. Démarrage du serveur..."
php artisan serve --host=0.0.0.0 --port=8000 > /dev/null 2>&1 &
SERVER_PID=$!
sleep 3

# 2. Test de la page catégories
echo "2. Test de la page catégories..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/categories)
echo "Code HTTP: $HTTP_CODE"

if [ "$HTTP_CODE" == "200" ]; then
    echo "✓ Page catégories accessible"
    
    # Vérifier le contenu
    echo -e "\nContenu de la page:"
    curl -s http://localhost:8000/categories | grep -o "<title>[^<]*</title>"
    
    # Tester avec des données
    echo -e "\nTest avec Tinker:"
    php artisan tinker <<'TINKER'
use App\Models\Categorie;
use App\Models\Produit;

try {
    // Créer une catégorie de test
    $categorie = Categorie::create([
        'nom' => 'Test Catégorie',
        'description' => 'Catégorie de test'
    ]);
    
    echo "Catégorie créée: {$categorie->nom}\n";
    
    // Tester la requête
    $categories = Categorie::where('archive', false)
        ->withCount(['sousCategories' => function($query) {
            $query->where('archive', false);
        }])
        ->get();
    
    echo "Nombre de catégories: " . $categories->count() . "\n";
    
    foreach ($categories as $cat) {
        $produitsCount = Produit::whereHas('sousCategorie', function($query) use ($cat) {
            $query->where('categorie_id', $cat->id)
                  ->where('archive', false);
        })
        ->where('archive', false)
        ->count();
        
        echo "Catégorie: {$cat->nom} - Produits: {$produitsCount}\n";
    }
    
    // Supprimer la catégorie de test
    $categorie->delete();
    echo "✓ Test réussi\n";
    
} catch (\Exception $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
}
TINKER
    
else
    echo "✗ Erreur: Page non accessible"
    
    # Voir les logs
    echo -e "\nDernières erreurs:"
    tail -20 storage/logs/laravel.log 2>/dev/null || echo "Pas de logs disponibles"
fi

# 3. Arrêter le serveur
kill $SERVER_PID 2>/dev/null
echo -e "\n=== TEST TERMINÉ ==="
