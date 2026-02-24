<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sous_categorie_id')->constrained()->onDelete('cascade');
            $table->string('code')->unique();
            $table->string('designation');
            $table->decimal('quantite_stock', 10, 2)->default(0);
            $table->decimal('prix_unitaire', 10, 2);
            $table->string('image')->nullable();
            $table->boolean('archive')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
