<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // Utiliser la façade Hash

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Gestionnaire Admin',
            'email' => 'gestionnaire@quincaillerie.com',
            'password' => Hash::make('password123'), // CORRECT
            'role' => 'gestionnaire'
        ]);

        User::create([
            'name' => 'Responsable Achat',
            'email' => 'achat@quincaillerie.com',
            'password' => Hash::make('password123'), // CORRECT
            'role' => 'responsable_achat'
        ]);

        User::create([
            'name' => 'Responsable Paiement',
            'email' => 'paiement@quincaillerie.com',
            'password' => Hash::make('password123'), // CORRECT
            'role' => 'responsable_paiement'
        ]);

        echo "Utilisateurs créés avec succès!\n";
        echo "Email: gestionnaire@quincaillerie.com | Mot de passe: password123\n";
        echo "Email: achat@quincaillerie.com | Mot de passe: password123\n";
        echo "Email: paiement@quincaillerie.com | Mot de passe: password123\n";
    }
}