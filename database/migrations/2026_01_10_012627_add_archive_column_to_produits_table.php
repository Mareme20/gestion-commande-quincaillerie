<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('produits', 'archive')) {
            Schema::table('produits', function (Blueprint $table) {
                $table->boolean('archive')->default(false)->after('image');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('produits', 'archive')) {
            Schema::table('produits', function (Blueprint $table) {
                $table->dropColumn('archive');
            });
        }
    }
};
