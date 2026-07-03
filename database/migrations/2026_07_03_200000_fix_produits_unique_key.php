<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter colonne produit_id
        if (!Schema::hasColumn('sync_produits', 'produit_id')) {
            Schema::table('sync_produits', function (Blueprint $table) {
                $table->bigInteger('produit_id')->nullable()->after('boutique_id');
            });
        }
        // Supprimer ancienne contrainte unique sur code_barre
        try {
            DB::statement('ALTER TABLE sync_produits DROP CONSTRAINT IF EXISTS sync_produits_boutique_id_code_barre_unique');
        } catch (\Exception $e) {}
        // Vider la table pour repartir propre (les donnees seront re-sync depuis les telephones)
        DB::table('sync_produits')->delete();
        // Nouvelle contrainte unique sur produit_id
        try {
            DB::statement('ALTER TABLE sync_produits ADD CONSTRAINT sync_produits_boutique_produit_unique UNIQUE (boutique_id, produit_id)');
        } catch (\Exception $e) {}
    }

    public function down(): void
    {
        //
    }
};
