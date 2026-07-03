<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_retours', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('boutique_id');
            $table->string('numero');
            $table->string('date')->nullable();
            $table->string('vente_numero')->nullable();
            $table->string('motif')->nullable();
            $table->string('type')->default('remboursement');
            $table->decimal('total', 12, 2)->default(0);
            $table->text('produits')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['boutique_id', 'numero']);
        });

        Schema::create('sync_mouvements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('boutique_id');
            $table->bigInteger('mouvement_id');
            $table->string('type')->default('sortie');
            $table->string('produit_nom')->nullable();
            $table->bigInteger('produit_id')->default(0);
            $table->integer('quantite')->default(0);
            $table->string('motif')->nullable();
            $table->string('date')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['boutique_id', 'mouvement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_retours');
        Schema::dropIfExists('sync_mouvements');
    }
};
