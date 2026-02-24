<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->foreignId('fournisseur_id')->nullable()->after('sous_categorie_id')->constrained()->nullOnDelete();
        });

        DB::statement("
            UPDATE produits p
            SET fournisseur_id = (
                SELECT MIN(cp.fournisseur_id)
                FROM commande_produit cp
                WHERE cp.produit_id = p.id
            )
            WHERE p.fournisseur_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fournisseur_id');
        });
    }
};
