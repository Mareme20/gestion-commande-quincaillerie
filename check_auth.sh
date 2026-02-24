#!/bin/bash

echo "=== VÉRIFICATION AUTHENTIFICATION ==="
echo ""

# Test avec curl en suivant les redirections
echo "Test /categories avec suivi de redirection:"
curl -s -L -o /tmp/categories_output.txt -w "%{url_effective}\n" http://localhost:8000/categories

echo ""
echo "Vérification des cookies/session:"
if [ -f "storage/framework/sessions/"* ]; then
    ls -la storage/framework/sessions/ | head -5
else
    echo "Pas de fichiers de session"
fi

echo ""
echo "Test de connexion simple:"
php artisan tinker <<'TINKER'
use Illuminate\Support\Facades\Auth;

echo "Authentifié: " . (Auth::check() ? 'OUI' : 'NON') . "\n";
echo "Utilisateur: " . (Auth::user() ? Auth::user()->email : 'NON') . "\n";

// Vérifier les permissions
if (Auth::check()) {
    $user = Auth::user();
    echo "Rôle: " . $user->role . "\n";
    echo "Est gestionnaire: " . ($user->isGestionnaire() ? 'OUI' : 'NON') . "\n";
    
    // Tester le Gate
    echo "Gate 'gestionnaire': " . (app('Illuminate\\Contracts\\Auth\\Access\\Gate')->allows('gestionnaire') ? 'AUTORISÉ' : 'NON AUTORISÉ') . "\n";
}
TINKER
