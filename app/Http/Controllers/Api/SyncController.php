<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    /**
     * Status de l API
     * GET /api/sync/status
     */
    public function status()
    {
        return response()->json([
            'status'  => 'online',
            'version' => '2.0',
            'app'     => 'Sawik SaaS',
        ]);
    }

    /**
     * Synchroniser toutes les donnees de la boutique
     * POST /api/sync
     */
    public function sync(Request $request)
    {
        $boutique = $request->boutique;

        try {
            DB::beginTransaction();

            // Sync Ventes
            if ($request->has('ventes')) {
                foreach ($request->ventes as $vente) {
                    DB::table('sync_ventes')->updateOrInsert(
                        ['boutique_id' => $boutique->id, 'numero' => $vente['numero']],
                        [
                            'boutique_id'   => $boutique->id,
                            'numero'        => $vente['numero'],
                            'date'          => $vente['date'],
                            'total'         => $vente['total'],
                            'mode_paiement' => $vente['modePaiement'] ?? 'Especes',
                            'produits'      => json_encode($vente['produits'] ?? []),
                            'client'        => json_encode($vente['client'] ?? null),
                            'synced_at'     => now(),
                        ]
                    );
                }
            }

            // Sync Produits/Stock
            if ($request->has('produits')) {
                foreach ($request->produits as $produit) {
                    DB::table('sync_produits')->updateOrInsert(
                        ['boutique_id' => $boutique->id, 'code_barre' => $produit['codeBarre'] ?? $produit['id']],
                        [
                            'boutique_id'   => $boutique->id,
                            'nom'           => $produit['nom'],
                            'prix'          => $produit['prix'],
                            'prix_achat'    => $produit['prixAchat'] ?? 0,
                            'categorie'     => $produit['categorie'] ?? '',
                            'quantite'      => $produit['quantite'] ?? 0,
                            'seuil_alerte'  => $produit['seuilAlerte'] ?? 5,
                            'icone'         => $produit['icone'] ?? '',
                            'code_barre'    => $produit['codeBarre'] ?? '',
                            'synced_at'     => now(),
                        ]
                    );
                }
            }

            // Sync Clients
            if ($request->has('clients')) {
                foreach ($request->clients as $client) {
                    DB::table('sync_clients')->updateOrInsert(
                        ['boutique_id' => $boutique->id, 'telephone' => $client['telephone']],
                        [
                            'boutique_id'     => $boutique->id,
                            'nom'             => $client['nom'],
                            'prenom'          => $client['prenom'] ?? '',
                            'telephone'       => $client['telephone'],
                            'quartier'        => $client['quartier'] ?? '',
                            'solde_credit'    => $client['solde_credit'] ?? 0,
                            'limite_credit'   => $client['limite_credit'] ?? 50000,
                            'synced_at'       => now(),
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'message'  => 'Synchronisation reussie',
                'boutique' => $boutique->nom,
                'synced_at'=> now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur sync: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Recuperer les donnees depuis le cloud
     * GET /api/sync/pull
     */
    public function pull(Request $request)
    {
        $boutique = $request->boutique;

        $ventes   = DB::table('sync_ventes')->where('boutique_id', $boutique->id)
                      ->orderBy('date', 'desc')->limit(500)->get();
        $produits = DB::table('sync_produits')->where('boutique_id', $boutique->id)->get();
        $clients  = DB::table('sync_clients')->where('boutique_id', $boutique->id)->get();

        return response()->json([
            'ventes'   => $ventes,
            'produits' => $produits,
            'clients'  => $clients,
            'synced_at'=> now()->toIso8601String(),
        ]);
    }
}
