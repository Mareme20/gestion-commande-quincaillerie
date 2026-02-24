<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorieSeeder::class,
            FournisseurSeeder::class,
            ProduitSeeder::class,
            CommandeSeeder::class,
            VersementSeeder::class,
        ]);
    }
}
