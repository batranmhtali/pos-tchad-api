<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Colonnes ajoutees uniquement si elles n'existent pas
        if (!Schema::hasColumn('boutiques', 'proprietaire_telephone')) {
            Schema::table('boutiques', function (Blueprint $table) {
                $table->string('proprietaire_telephone')->nullable();
            });
        }
        if (!Schema::hasColumn('boutiques', 'est_principale')) {
            Schema::table('boutiques', function (Blueprint $table) {
                $table->boolean('est_principale')->default(true);
            });
        }
    }

    public function down(): void {}
};
