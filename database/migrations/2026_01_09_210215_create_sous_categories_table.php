<?php
// database/migrations/xxxx_xx_xx_create_sous_categories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sous_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categorie_id')->constrained()->onDelete('cascade');
            $table->string('nom');
            $table->text('description')->nullable();
            $table->boolean('archive')->default(false);
            $table->timestamps();
            
            // Index pour optimiser les recherches
            $table->index('categorie_id');
            $table->index('archive');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sous_categories');
    }
};