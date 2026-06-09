<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProduitController;
use App\Http\Controllers\Api\VenteController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\SyncController;

// Routes publiques (sans auth)
Route::post('/login', [AuthController::class, 'login']);
Route::get('/sync/status', [SyncController::class, 'status']);

// Routes protégées par JWT
Route::middleware('auth:api')->group(function () {
    Route::post('/logout',     [AuthController::class, 'logout']);
    Route::get('/me',          [AuthController::class, 'me']);
    Route::apiResource('produits', ProduitController::class);
    Route::apiResource('ventes',   VenteController::class);
    Route::apiResource('clients',  ClientController::class);
    Route::post('/sync',       [SyncController::class, 'sync']);
});

// ─── Routes SaaS Boutiques ────────────────────────────────────
use App\Http\Controllers\Api\BoutiqueController;
use App\Http\Middleware\AuthBoutique;

Route::prefix('boutiques')->group(function () {
    Route::post('/inscrire',     [BoutiqueController::class, 'inscrire']);
    Route::post('/connexion',    [BoutiqueController::class, 'connexion']);
    // OTP publics (pas de token requis — récupération de mot de passe)
    Route::post('/otp-demander', [BoutiqueController::class, 'demanderOTP']);
    Route::post('/otp-verifier', [BoutiqueController::class, 'verifierOTP']);
});

Route::prefix('boutiques')->middleware(AuthBoutique::class)->group(function () {
    Route::get('/profil',     [BoutiqueController::class, 'profil']);
    Route::put('/profil',     [BoutiqueController::class, 'mettreAJour']);
});


// Multi-boutique
Route::middleware(\App\Http\Middleware\AuthBoutique::class)->group(function () {
    Route::get('/boutiques',          [\App\Http\Controllers\Api\BoutiqueController::class, 'lister']);
    Route::post('/boutiques/ajouter', [\App\Http\Controllers\Api\BoutiqueController::class, 'ajouterBoutique']);
});






// Routes Synchronisation
Route::middleware(\App\Http\Middleware\AuthBoutique::class)->group(function () {
    Route::post('/sync',      [\App\Http\Controllers\Api\SyncController::class, 'sync']);
    Route::get('/sync/pull',  [\App\Http\Controllers\Api\SyncController::class, 'pull']);
});

// ─── Routes Telegram Bot ─────────────────────────────────────
use App\Http\Controllers\Api\TelegramController;

// Webhook Telegram (appelé automatiquement par Telegram)
Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);
// À appeler une seule fois pour enregistrer le webhook
Route::get('/telegram/set-webhook', [TelegramController::class, 'setWebhook']);

// ─── Routes Admin ────────────────────────────────────────────
use App\Http\Controllers\Api\AdminController;

Route::prefix('admin')->middleware('auth:api')->group(function () {
    Route::get('/utilisateurs',              [AdminController::class, 'listeUtilisateurs']);
    Route::put('/utilisateurs/{id}/abonnement', [AdminController::class, 'modifierAbonnement']);
    Route::put('/utilisateurs/{id}/suspendre',  [AdminController::class, 'suspendreUtilisateur']);
    Route::get('/statistiques',              [AdminController::class, 'statistiques']);
});

Route::prefix('admin')->middleware('auth:api')->group(function () {
    Route::post('/utilisateurs/creer',           [AdminController::class, 'ajouterUtilisateur']);
    Route::put('/utilisateurs/{id}/password',    [AdminController::class, 'changerMotDePasse']);

    // ─── Gestion Boutiques SaaS ───────────────────────────────
    Route::get('/boutiques-saas',                [AdminController::class, 'listeBoutiques']);
    Route::get('/boutiques-saas/stats',          [AdminController::class, 'statistiquesBoutiques']);
    Route::put('/boutiques/{id}/abonnement',     [AdminController::class, 'modifierAbonnementBoutique']);
    Route::put('/boutiques/{id}/suspendre',      [AdminController::class, 'suspendreBoutique']);
    Route::put('/boutiques/{id}/password',       [AdminController::class, 'changerMotDePasseBoutique']);
});

