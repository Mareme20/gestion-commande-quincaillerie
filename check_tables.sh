#!/bin/bash

echo "=== VÉRIFICATION DE TOUTES LES TABLES ==="
echo ""

TABLES="users categories sous_categories produits fournisseurs commandes versements commande_produit"

for TABLE in $TABLES; do
    echo "Table: $TABLE"
    echo "------------------------"
    mysql -u quincaillerie_user -pQuincaillerie123! -D quincaillerie_db -e "DESCRIBE $TABLE" 2>/dev/null || echo "Table $TABLE n'existe pas"
    echo ""
done

echo "=== COLONNES 'archive' PAR TABLE ==="
echo ""

for TABLE in $TABLES; do
    COUNT=$(mysql -u quincaillerie_user -pQuincaillerie123! -D quincaillerie_db -e "SHOW COLUMNS FROM $TABLE LIKE 'archive'" 2>/dev/null | wc -l)
    if [ $COUNT -gt 1 ]; then
        echo "✓ $TABLE a une colonne 'archive'"
    else
        echo "✗ $TABLE n'a PAS de colonne 'archive'"
    fi
done
