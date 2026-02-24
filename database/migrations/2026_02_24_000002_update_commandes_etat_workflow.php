<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE commandes MODIFY etat ENUM('brouillon','validee','recue','cloturee','annule','en_cours','livre','paye') NOT NULL DEFAULT 'brouillon'");

        DB::table('commandes')->where('etat', 'en_cours')->update(['etat' => 'validee']);
        DB::table('commandes')->where('etat', 'livre')->update(['etat' => 'recue']);
        DB::table('commandes')->where('etat', 'paye')->update(['etat' => 'cloturee']);

        DB::statement("ALTER TABLE commandes MODIFY etat ENUM('brouillon','validee','recue','cloturee','annule') NOT NULL DEFAULT 'brouillon'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE commandes MODIFY etat ENUM('brouillon','validee','recue','cloturee','annule','en_cours','livre','paye') NOT NULL DEFAULT 'validee'");

        DB::table('commandes')->where('etat', 'validee')->update(['etat' => 'en_cours']);
        DB::table('commandes')->where('etat', 'recue')->update(['etat' => 'livre']);
        DB::table('commandes')->where('etat', 'cloturee')->update(['etat' => 'paye']);

        DB::statement("ALTER TABLE commandes MODIFY etat ENUM('en_cours','livre','paye','annule') NOT NULL DEFAULT 'en_cours'");
    }
};
