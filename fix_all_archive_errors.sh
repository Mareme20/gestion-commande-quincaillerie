#!/bin/bash

echo "=== CORRECTION DE TOUTES LES ERREURS 'archive' AMBIGUËS ==="

# Liste des fichiers à corriger
FILES=(
    "app/Http/Controllers/Web/CategorieController.php"
    "app/Http/Controllers/Web/ProduitController.php"
    "app/Http/Controllers/Web/FournisseurController.php"
    "app/Http/Controllers/Web/CommandeController.php"
    "app/Http/Controllers/FournisseurController.php"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "Correction de $file..."
        
        # Sauvegarder
        cp "$file" "${file}.backup"
        
        # Remplacer les where ambigus
        sed -i "
            # Catégories avec produits
            s/->where('archive', false)/->where('produits.archive', false)/g
            s/->where(\"archive\", false)/->where(\"produits.archive\", false)/g
            
            # Produits seuls
            s/Produit::where('archive'/Produit::where('produits.archive'/g
            s/Produit::where(\"archive\"/Produit::where(\"produits.archive\"/g
            
            # Fournisseurs seuls
            s/Fournisseur::where('archive'/Fournisseur::where('fournisseurs.archive'/g
            s/Fournisseur::where(\"archive\"/Fournisseur::where(\"fournisseurs.archive\"/g
            
            # Catégories seules
            s/Categorie::where('archive'/Categorie::where('categories.archive'/g
            s/Categorie::where(\"archive\"/Categorie::where(\"categories.archive\"/g
            
            # Sous-catégories
            s/->where('archive', false)/->where('sous_categories.archive', false)/g
            s/->where(\"archive\", false)/->where(\"sous_categories.archive\", false)/g
        " "$file"
        
        echo "✓ $file corrigé"
    else
        echo "⚠ $file non trouvé"
    fi
done

# Fichiers spécifiques supplémentaires
echo "Correction des fichiers spécifiques..."

# CategorieController - lignes spécifiques
if [ -f "app/Http/Controllers/Web/CategorieController.php" ]; then
    sed -i "
        # Ligne 1
        s/->where('archive', false)/->where('produits.archive', false)/g
        # Ligne 2
        s/->where('archive', false)/->where('sous_categories.archive', false)/g
        # Ligne 3
        s/->where('archive', false)/->where('sous_categories.archive', false)/g
        # Ligne 4
        s/->where('archive', false)/->where('produits.archive', false)/g
    " "app/Http/Controllers/Web/CategorieController.php"
fi

# ProduitController - lignes spécifiques
if [ -f "app/Http/Controllers/Web/ProduitController.php" ]; then
    sed -i "
        # Ligne 1
        s/->where('archive', false)/->where('produits.archive', false)/g
        # Ligne 2
        s/Categorie::where('archive'/Categorie::where('categories.archive'/g
        # Ligne 3
        s/Categorie::where('archive'/Categorie::where('categories.archive'/g
        # Ligne 4
        s/Categorie::where('archive'/Categorie::where('categories.archive'/g
        # Ligne 5
        s/->where('archive', false)/->where('sous_categories.archive', false)/g
    " "app/Http/Controllers/Web/ProduitController.php"
fi

# Nettoyage
echo "Nettoyage des caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "=== CORRECTION TERMINÉE ==="
echo "Les modifications ont été sauvegardées avec l'extension .backup"
