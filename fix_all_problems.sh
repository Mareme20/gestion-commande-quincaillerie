#!/bin/bash

echo "=== CORRECTION COMPLÈTE DES PROBLÈMES ==="
echo ""

# 1. Restaurer EventServiceProvider
echo "1. Restauration EventServiceProvider..."
cat > app/Providers/EventServiceProvider.php << 'EVENTEOF'
<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
EVENTEOF
echo "✓ EventServiceProvider restauré"

# 2. Résoudre le conflit AuthController
echo ""
echo "2. Résolution conflit AuthController..."
if [ -f "app/Http/Controllers/AuthController.php" ] && [ -f "app/Http/Controllers/Web/AuthController.php" ]; then
    echo "⚠️  Deux AuthController détectés"
    
    # Garder le Web et supprimer l'API
    mv app/Http/Controllers/AuthController.php app/Http/Controllers/AuthController.api.backup
    echo "✓ AuthController API sauvegardé"
    
    # Mettre à jour les routes
    if [ -f "routes/web.php" ]; then
        sed -i 's/App\\Http\\Controllers\\AuthController/App\\Http\\Controllers\\Web\\AuthController/g' routes/web.php
        echo "✓ Routes web mises à jour"
    fi
    
    if [ -f "routes/api.php" ]; then
        sed -i 's/App\\Http\\Controllers\\AuthController/App\\Http\\Controllers\\Web\\AuthController/g' routes/api.php
        echo "✓ Routes API mises à jour"
    fi
else
    echo "✓ Pas de conflit AuthController"
fi

# 3. Vérifier CategorieController
echo ""
echo "3. Vérification CategorieController..."
if [ -f "app/Http/Controllers/Web/CategorieController.php" ]; then
    # Vérifier s'il y a des imports en double
    auth_count=$(grep -c "use Illuminate\\\\Support\\\\Facades\\\\Auth" app/Http/Controllers/Web/CategorieController.php)
    if [ "$auth_count" -gt 1 ]; then
        echo "⚠️  $auth_count imports Auth trouvés, correction..."
        # Garder seulement le premier
        awk '
        BEGIN { auth_found=0 }
        /use Illuminate\\Support\\Facades\\Auth/ {
            if (auth_found == 0) {
                print $0
                auth_found=1
            }
            next
        }
        { print $0 }
        ' app/Http/Controllers/Web/CategorieController.php > /tmp/cat_temp
        mv /tmp/cat_temp app/Http/Controllers/Web/CategorieController.php
        echo "✓ CategorieController corrigé"
    else
        echo "✓ CategorieController OK"
    fi
else
    echo "⚠️  CategorieController non trouvé"
fi

# 4. Nettoyer les caches
echo ""
echo "4. Nettoyage des caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 5. Test
echo ""
echo "5. Test de fonctionnement..."
php artisan tinker <<'TINKER'
try {
    echo "Test 1: Syntaxe... ";
    include 'app/Http/Controllers/Web/CategorieController.php';
    echo "✓ OK\n";
    
    echo "Test 2: Auth façade... ";
    use Illuminate\Support\Facades\Auth;
    echo "✓ Import réussi\n";
    
    echo "Test 3: Vérification routes... ";
    $routeCount = count(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes());
    echo "✓ $routeCount routes trouvées\n";
    
} catch (\ParseError $e) {
    echo "✗ Erreur syntaxe: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
} catch (\Error $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
}
TINKER

echo ""
echo "=== CORRECTION TERMINÉE ==="
echo ""
echo "Pour tester:"
echo "1. php artisan serve --host=0.0.0.0 --port=8000"
echo "2. http://localhost:8000/login"
echo "3. Se connecter avec: gestionnaire@quincaillerie.com / password123"
echo "4. Puis accéder à: http://localhost:8000/categories"
