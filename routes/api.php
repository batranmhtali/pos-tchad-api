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
    Route::post('/inscrire',  [BoutiqueController::class, 'inscrire']);
    Route::post('/connexion', [BoutiqueController::class, 'connexion']);
});

Route::prefix('boutiques')->middleware(AuthBoutique::class)->group(function () {
    Route::get('/profil',     [BoutiqueController::class, 'profil']);
    Route::put('/profil',     [BoutiqueController::class, 'mettreAJour']);
});

