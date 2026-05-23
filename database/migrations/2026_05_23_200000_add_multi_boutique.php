<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boutiques', function (Blueprint $table) {
            if (!Schema::hasColumn('boutiques', 'proprietaire_telephone')) {
                $table->string('proprietaire_telephone')->nullable();
            }
            if (!Schema::hasColumn('boutiques', 'est_principale')) {
                $table->boolean('est_principale')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('boutiques', function (Blueprint $table) {
            $table->dropColumn(['proprietaire_telephone', 'est_principale']);
        });
    }
};
