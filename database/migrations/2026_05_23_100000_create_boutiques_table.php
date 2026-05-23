<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boutiques', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('proprietaire');
            $table->string('telephone')->unique();
            $table->string('email')->nullable();
            $table->string('ville')->default("N'Djamena");
            $table->string('pays')->default('Tchad');
            $table->string('mot_de_passe_hash');
            $table->string('token_api')->unique()->nullable();
            $table->boolean('actif')->default(true);
            $table->enum('plan', ['essai', 'basic', 'pro', 'business'])->default('essai');
            $table->timestamp('essai_debut')->nullable();
            $table->timestamp('essai_fin')->nullable();
            $table->timestamp('abonnement_debut')->nullable();
            $table->timestamp('abonnement_fin')->nullable();
            $table->boolean('abonnement_actif')->default(true);
            $table->decimal('prix_mensuel', 10, 0)->default(5000);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boutiques');
    }
};
