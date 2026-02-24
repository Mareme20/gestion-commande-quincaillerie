#!/bin/bash

echo "=== CORRECTION DES HASH TROP COURTS ==="

# Générer un nouveau hash valide
echo "1. Génération d'un hash bcrypt valide..."
php artisan tinker --execute='
    use Illuminate\Support\Facades\Hash;
    $hash = Hash::make("password123");
    echo "HASH_VALIDE=\"$hash\"\n";
    echo "Longueur: " . strlen($hash) . "\n";
' | grep HASH_VALIDE > /tmp/hash_output.txt

# Extraire le hash
source /tmp/hash_output.txt
echo "Hash généré: ${HASH_VALIDE:0:30}..."

# Mettre à jour la base
echo "2. Mise à jour de la base de données..."
mysql -u quincaillerie_user -pQuincaillerie123! -D quincaillerie_db << MYSQL
UPDATE users SET password = '$HASH_VALIDE' 
WHERE email LIKE '%@quincaillerie.com';

-- Vérification finale
SELECT 
    email,
    role,
    LENGTH(password) as length,
    CASE 
        WHEN LENGTH(password) = 60 THEN '✓ OK'
        ELSE '✗ PROBLEME'
    END as length_check,
    CASE 
        WHEN password LIKE '\$2y\$10\$%' THEN '✓ Bcrypt'
        ELSE '✗ Format'
    END as format_check
FROM users;
MYSQL

# Test
echo "3. Test du hash..."
php artisan tinker --execute='
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Hash;
    
    $user = DB::table("users")->where("email", "gestionnaire@quincaillerie.com")->first();
    if ($user) {
        echo "Test Hash::check:\n";
        echo "  password123: " . (Hash::check("password123", $user->password) ? "✓ VALIDE" : "✗ INVALIDE") . "\n";
        echo "  wrongpass: " . (Hash::check("wrongpass", $user->password) ? "✗ PROBLEME" : "✓ OK") . "\n";
    }
'

echo "=== CORRECTION TERMINÉE ==="
echo "Testez maintenant: http://localhost:8000/login"
echo "Avec: gestionnaire@quincaillerie.com / password123"
