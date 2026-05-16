<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Utilisateurs
        Schema::create('utilisateurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom')->nullable();
            $table->string('telephone')->unique();
            $table->string('role')->default('caissier');
            $table->string('mot_de_passe_hash');
            $table->boolean('actif')->default(true);
            $table->timestamp('derniere_connexion')->nullable();
            $table->timestamps();
        });

        // Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->string('icone')->nullable();
            $table->string('couleur')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        // Produits
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categorie_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('code_barre')->unique()->nullable();
            $table->decimal('prix_vente', 12, 2)->default(0);
            $table->decimal('prix_achat', 12, 2)->default(0);
            $table->string('unite')->default('piece');
            $table->decimal('tva_pct', 5, 2)->default(0);
            $table->boolean('actif')->default(true);
            $table->string('photo_url')->nullable();
            $table->timestamps();
        });

        // Stock
        Schema::create('stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->unique()->constrained('produits')->cascadeOnDelete();
            $table->decimal('quantite_actuelle', 12, 2)->default(0);
            $table->decimal('seuil_alerte', 12, 2)->default(5);
            $table->decimal('quantite_max', 12, 2)->nullable();
            $table->timestamp('derniere_entree')->nullable();
            $table->timestamps();
        });

        // Clients
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom')->nullable();
            $table->string('telephone')->unique()->nullable();
            $table->string('adresse')->nullable();
            $table->string('quartier')->nullable();
            $table->decimal('solde_credit', 12, 2)->default(0);
            $table->decimal('limite_credit', 12, 2)->default(50000);
            $table->text('notes')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        // Ventes
        Schema::create('ventes', function (Blueprint $table) {
            $table->id();
            $table->string('numero_ticket')->unique();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('utilisateur_id')->constrained('utilisateurs');
            $table->decimal('montant_total', 12, 2)->default(0);
            $table->decimal('montant_paye', 12, 2)->default(0);
            $table->decimal('montant_credit', 12, 2)->default(0);
            $table->decimal('remise_globale', 12, 2)->default(0);
            $table->string('statut')->default('payee');
            $table->text('note')->nullable();
            $table->timestamp('date_vente')->useCurrent();
            $table->timestamps();
        });

        // Lignes vente
        Schema::create('lignes_vente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vente_id')->constrained('ventes')->cascadeOnDelete();
            $table->foreignId('produit_id')->constrained('produits');
            $table->string('nom_produit');
            $table->decimal('quantite', 12, 2)->default(1);
            $table->decimal('prix_unitaire', 12, 2);
            $table->decimal('remise_pct', 5, 2)->default(0);
            $table->decimal('sous_total', 12, 2);
            $table->timestamps();
        });

        // Paiements
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vente_id')->constrained('ventes')->cascadeOnDelete();
            $table->decimal('montant', 12, 2);
            $table->string('mode_paiement')->default('especes');
            $table->string('reference_mm')->nullable();
            $table->string('telephone_mm')->nullable();
            $table->string('statut')->default('confirme');
            $table->timestamps();
        });

        // Fournisseurs
        Schema::create('fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('contact')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('ville')->nullable();
            $table->string('pays')->default('Tchad');
            $table->decimal('solde_dette', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        // Mouvements stock
        Schema::create('mouvements_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produit_id')->constrained('produits');
            $table->string('type_mouvement');
            $table->decimal('quantite', 12, 2);
            $table->decimal('stock_avant', 12, 2);
            $table->decimal('stock_apres', 12, 2);
            $table->string('motif')->nullable();
            $table->foreignId('vente_id')->nullable()->constrained('ventes')->nullOnDelete();
            $table->foreignId('fournisseur_id')->nullable()->constrained('fournisseurs')->nullOnDelete();
            $table->foreignId('utilisateur_id')->constrained('utilisateurs');
            $table->timestamps();
        });

        // Sync log
        Schema::create('sync_log', function (Blueprint $table) {
            $table->id();
            $table->string('appareil_id');
            $table->string('table_cible');
            $table->string('operation');
            $table->unsignedBigInteger('enregistrement_id');
            $table->json('payload');
            $table->string('statut')->default('succes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_log');
        Schema::dropIfExists('mouvements_stock');
        Schema::dropIfExists('fournisseurs');
        Schema::dropIfExists('paiements');
        Schema::dropIfExists('lignes_vente');
        Schema::dropIfExists('ventes');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('stock');
        Schema::dropIfExists('produits');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('utilisateurs');
    }
};
