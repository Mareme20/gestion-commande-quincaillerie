#!/bin/bash

echo "=== CORRECTION DES IMPORTS AUTH ==="
echo ""

# Trouver tous les fichiers avec use Auth
echo "Fichiers utilisant Auth:"
grep -l "use.*Auth" app/*.php app/**/*.php 2>/dev/null

echo ""
echo "Analyse des imports problématiques..."

for file in $(grep -l "use.*Auth" app/*.php app/**/*.php 2>/dev/null); do
    echo ""
    echo "Fichier: $file"
    echo "------------------------"
    
    # Compter les lignes use Auth
    auth_count=$(grep -c "use.*Auth" "$file")
    
    if [ "$auth_count" -gt 1 ]; then
        echo "⚠️  $auth_count imports Auth trouvés"
        
        # Afficher les lignes problématiques
        grep -n "use.*Auth" "$file"
        
        # Corriger automatiquement (garder seulement le premier)
        echo "Correction..."
        
        # Créer une copie de sauvegarde
        cp "$file" "${file}.backup"
        
        # Solution: garder seulement le premier use Auth
        # Créer un nouveau fichier sans les doublons
        awk '
        BEGIN { auth_found=0 }
        /use.*Auth/ {
            if (auth_found == 0) {
                print $0
                auth_found=1
            }
            next
        }
        { print $0 }
        ' "$file" > "${file}.temp"
        
        mv "${file}.temp" "$file"
        echo "✓ Fichier corrigé"
    else
        echo "✓ OK (1 import seulement)"
    fi
done

echo ""
echo "=== VÉRIFICATION APRÈS CORRECTION ==="

# Test avec tinker
php artisan tinker <<'TINKER'
try {
    // Test d'import Auth
    use Illuminate\Support\Facades\Auth;
    echo "✓ Import Auth réussi\n";
    
    // Test si Auth fonctionne
    echo "Auth check: " . (Auth::check() ? 'OUI' : 'NON') . "\n";
    
} catch (\Error $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
}
TINKER

echo ""
echo "=== CORRECTION TERMINÉE ==="
