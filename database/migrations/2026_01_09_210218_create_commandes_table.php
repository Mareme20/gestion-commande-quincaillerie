<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fournisseur_id')->constrained()->onDelete('cascade');
            $table->date('date_commande');
            $table->decimal('montant_total', 15, 2);
            $table->date('date_livraison_prevue');
            $table->date('date_livraison_reelle')->nullable();
            $table->enum('etat', ['en_cours', 'livre', 'paye', 'annule'])->default('en_cours');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};