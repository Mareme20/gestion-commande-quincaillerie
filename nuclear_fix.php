<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "=== RÉINITIALISATION COMPLÈTE ===\n\n";

// 1. Supprimer tous les utilisateurs
User::truncate();
echo "1. Table users vidée\n";

// 2. Créer un utilisateur SIMPLE
$user = User::create([
    'name' => 'Test User',
    'email' => 'test@test.com',
    'password' => Hash::make('simplepass'), // Hash VALIDE généré par Laravel
    'role' => 'gestionnaire',
]);

echo "2. Utilisateur créé:\n";
echo "   Email: test@test.com\n";
echo "   Mot de passe: simplepass\n";
echo "   Hash: " . substr($user->password, 0, 30) . "...\n";
echo "   Longueur: " . strlen($user->password) . " caractères\n";

// 3. Tester immédiatement
echo "\n3. Test du hash:\n";
$check = Hash::check('simplepass', $user->password);
echo "   Hash::check: " . ($check ? '✓ OK' : '✗ ÉCHEC') . "\n";

if (!$check) {
    echo "   ERREUR: Le hash généré n'est pas valide!\n";
    echo "   Cela indique un problème avec bcrypt sur votre système.\n";
}
