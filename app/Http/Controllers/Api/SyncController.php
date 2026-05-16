<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    /**
     * Endpoint principal de synchronisation.
     * Reçoit les données locales et retourne les mises à jour du serveur.
     */
    public function sync(Request $request)
    {
        $data          = $request->validate(['payload' => 'required|array']);
        $payload       = $data['payload'];
        $lastSyncAt    = $request->input('last_sync_at');
        $resultats     = [];

        DB::beginTransaction();
        try {
            // Traiter chaque opération de la file de sync
            foreach ($payload as $operation) {
                $table     = $operation['table_cible'] ?? null;
                $op        = $operation['operation'] ?? null;
                $opPayload = $operation['payload'] ?? [];
                $localId   = $operation['enregistrement_id'] ?? null;

                if (!$table || !$op) continue;

                switch ($op) {
                    case 'CREATE':
                        $serverId = DB::table($table)->insertGetId(
                            array_merge($opPayload, ['created_at' => now(), 'updated_at' => now()])
                        );
                        $resultats[] = [
                            'table'    => $table,
                            'local_id' => $localId,
                            'server_id'=> $serverId,
                            'statut'   => 'ok',
                        ];
                        break;

                    case 'UPDATE':
                        DB::table($table)
                            ->where('id', $localId)
                            ->update(array_merge($opPayload, ['updated_at' => now()]));
                        $resultats[] = [
                            'table'    => $table,
                            'local_id' => $localId,
                            'statut'   => 'ok',
                        ];
                        break;

                    case 'DELETE':
                        DB::table($table)->where('id', $localId)->delete();
                        $resultats[] = [
                            'table'    => $table,
                            'local_id' => $localId,
                            'statut'   => 'ok',
                        ];
                        break;
                }
            }

            DB::commit();

            // Retourner les données mises à jour depuis le serveur
            $miseAJour = [];
            if ($lastSyncAt) {
                $tables = ['produits', 'clients', 'ventes', 'stock'];
                foreach ($tables as $table) {
                    if (DB::getSchemaBuilder()->hasTable($table)) {
                        $miseAJour[$table] = DB::table($table)
                            ->where('updated_at', '>', $lastSyncAt)
                            ->get();
                    }
                }
            }

            return response()->json([
                'succes'       => true,
                'resultats'    => $resultats,
                'mise_a_jour'  => $miseAJour,
                'synced_at'    => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'succes'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function status()
    {
        return response()->json([
            'status'     => 'ok',
            'version'    => '1.0.0',
            'server_time'=> now()->toIso8601String(),
        ]);
    }
}
