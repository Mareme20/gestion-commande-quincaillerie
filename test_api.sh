#!/bin/bash

# Configuration
BASE_URL="http://localhost:8000/api"
EMAIL="achat@quincaillerie.com"
PASSWORD="password123"

echo "=== Test de l'API Quincaillerie ==="
echo ""

# 1. Test de connexion
echo "1. Authentification..."
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/login" \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")

TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)
echo "Token reçu: ${TOKEN:0:20}..."
echo ""

# 2. Test des catégories
echo "2. Liste des catégories..."
curl -s -X GET "$BASE_URL/categories" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" | jq '. | length' | xargs echo "Nombre de catégories:"
echo ""

# 3. Test des produits
echo "3. Liste des produits..."
curl -s -X GET "$BASE_URL/produits" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" | jq '. | length' | xargs echo "Nombre de produits:"
echo ""

# 4. Test du dashboard
echo "4. Statistiques du dashboard..."
curl -s -X GET "$BASE_URL/dashboard/statistiques" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" | jq '.statistiques_principales'
echo ""

# 5. Déconnexion
echo "5. Déconnexion..."
LOGOUT_RESPONSE=$(curl -s -X POST "$BASE_URL/logout" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json")
echo $LOGOUT_RESPONSE
echo ""
echo "=== Test terminé ==="