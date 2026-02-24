#!/bin/bash

echo "=== RÉINITIALISATION COMPLÈTE ==="

# 1. Arrêter tout
pkill -f "php artisan" 2>/dev/null || true

# 2. Supprimer la base
mysql -u root -p <<MYSQL
DROP DATABASE IF EXISTS quincaillerie_db;
CREATE DATABASE quincaillerie_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON quincaillerie_db.* TO 'quincaillerie_user'@'localhost' IDENTIFIED BY 'Quincaillerie123!';
FLUSH PRIVILEGES;
MYSQL

# 3. Recréer les modèles simples
cat > app/Models/User.php << 'MODEL'
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable {
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = ['role','name','email','password'];
    protected $hidden = ['password','remember_token'];
    public function isGestionnaire() { return $this->role === 'gestionnaire'; }
    public function isResponsableAchat() { return $this->role === 'responsable_achat'; }
    public function isResponsablePaiement() { return $this->role === 'responsable_paiement'; }
}
MODEL

# 4. Migration et seeder simple
php artisan migrate:fresh --force

# 5. Créer UN SEUL utilisateur de test
php artisan tinker <<TINKER
use App\Models\User;
use Illuminate\Support\Facades\Hash;
User::create([
    'name' => 'Admin Test',
    'email' => 'admin@test.com',
    'password' => Hash::make('simplepassword'),
    'role' => 'gestionnaire'
]);
echo "✓ Utilisateur créé: admin@test.com / simplepassword\n";
TINKER

# 6. Nettoyer
php artisan config:clear
php artisan cache:clear

echo -e "\n=== TESTEZ MAINTENANT ==="
echo "php artisan serve --host=0.0.0.0 --port=8000"
echo "http://localhost:8000/login"
echo "Email: admin@test.com"
echo "Mot de passe: simplepassword"
