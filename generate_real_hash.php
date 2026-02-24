<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;

echo "=== Génération de hash VALIDE ===\n\n";

// Hash pour 'password123'
$hash = Hash::make('password123');
echo "Hash pour 'password123':\n";
echo $hash . "\n";
echo "Longueur: " . strlen($hash) . " caractères (doit être 60)\n";
echo "Format: " . (preg_match('/^\$2[ay]\$\d{2}\$/', $hash) ? '✓ Bcrypt valide' : '✗ Format invalide') . "\n";

// Vérification
echo "\nVérification: ";
echo (Hash::check('password123', $hash) ? '✓ OK' : '✗ ÉCHEC') . "\n";
