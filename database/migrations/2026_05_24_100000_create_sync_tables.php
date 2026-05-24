<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_ventes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('boutique_id');
            $table->string('numero')->unique();
            $table->string('date');
            $table->decimal('total', 10, 0);
            $table->string('mode_paiement')->default('Especes');
            $table->json('produits')->nullable();
            $table->json('client')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->index(['boutique_id', 'date']);
        });

        Schema::create('sync_produits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('boutique_id');
            $table->string('nom');
            $table->decimal('prix', 10, 0)->default(0);
            $table->decimal('prix_achat', 10, 0)->default(0);
            $table->string('categorie')->nullable();
            $table->integer('quantite')->default(0);
            $table->integer('seuil_alerte')->default(5);
            $table->string('icone')->nullable();
            $table->string('code_barre')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['boutique_id', 'code_barre']);
        });

        Schema::create('sync_clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('boutique_id');
            $table->string('nom');
            $table->string('prenom')->nullable();
            $table->string('telephone');
            $table->string('quartier')->nullable();
            $table->decimal('solde_credit', 10, 0)->default(0);
            $table->decimal('limite_credit', 10, 0)->default(50000);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['boutique_id', 'telephone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_ventes');
        Schema::dropIfExists('sync_produits');
        Schema::dropIfExists('sync_clients');
    }
};
