<?php
// database/seeders/FixUsersSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FixUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Supprimer les utilisateurs existants
        User::truncate();
        
        // Recréer les utilisateurs avec Hash correct
        $users = [
            [
                'name' => 'Gestionnaire Admin',
                'email' => 'gestionnaire@quincaillerie.com',
                'password' => 'password123',
                'role' => 'gestionnaire'
            ],
            [
                'name' => 'Responsable Achat',
                'email' => 'achat@quincaillerie.com',
                'password' => 'password123',
                'role' => 'responsable_achat'
            ],
            [
                'name' => 'Responsable Paiement',
                'email' => 'paiement@quincaillerie.com',
                'password' => 'password123',
                'role' => 'responsable_paiement'
            ],
        ];
        
        foreach ($users as $userData) {
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']), // IMPORTANT: utiliser Hash::make
                'role' => $userData['role']
            ]);
            
            echo "Utilisateur créé: {$userData['email']}\n";
        }
        
        echo "\nComptes de test créés:\n";
        echo "Email: gestionnaire@quincaillerie.com | Mot de passe: password123\n";
        echo "Email: achat@quincaillerie.com | Mot de passe: password123\n";
        echo "Email: paiement@quincaillerie.com | Mot de passe: password123\n";
    }
}