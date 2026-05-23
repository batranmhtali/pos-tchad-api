<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter proprietaire_id pour lier plusieurs boutiques
        Schema::table('boutiques', function (Blueprint $table) {
            $table->string('proprietaire_telephone')->nullable()->after('proprietaire');
            $table->boolean('est_principale')->default(true)->after('actif');
        });
    }

    public function down(): void
    {
        Schema::table('boutiques', function (Blueprint $table) {
            $table->dropColumn(['proprietaire_telephone', 'est_principale']);
        });
    }
};
