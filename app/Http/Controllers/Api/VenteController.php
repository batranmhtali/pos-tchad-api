<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VenteController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('ventes')
            ->leftJoin('clients', 'ventes.client_id', '=', 'clients.id')
            ->select('ventes.*', 'clients.nom as client_nom')
            ->orderBy('ventes.created_at', 'desc');

        if ($request->has('date')) {
            $query->whereDate('ventes.date_vente', $request->date);
        }

        return response()->json(['data' => $query->limit(100)->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'numero_ticket'  => 'required|string|unique:ventes',
            'montant_total'  => 'required|numeric|min:0',
            'montant_paye'   => 'required|numeric|min:0',
            'statut'         => 'required|string',
            'lignes'         => 'required|array',
            'client_id'      => 'nullable|integer',
            'utilisateur_id' => 'required|integer',
        ]);

        $venteId = DB::table('ventes')->insertGetId([
            'numero_ticket'   => $data['numero_ticket'],
            'client_id'       => $data['client_id'] ?? null,
            'utilisateur_id'  => $data['utilisateur_id'],
            'montant_total'   => $data['montant_total'],
            'montant_paye'    => $data['montant_paye'],
            'montant_credit'  => $data['montant_total'] - $data['montant_paye'],
            'statut'          => $data['statut'],
            'date_vente'      => now(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        foreach ($data['lignes'] as $ligne) {
            DB::table('lignes_vente')->insert([
                'vente_id'      => $venteId,
                'produit_id'    => $ligne['produit_id'],
                'nom_produit'   => $ligne['nom_produit'],
                'quantite'      => $ligne['quantite'],
                'prix_unitaire' => $ligne['prix_unitaire'],
                'sous_total'    => $ligne['sous_total'],
                'created_at'    => now(),
            ]);
        }

        return response()->json(['data' => ['id' => $venteId]], 201);
    }

    public function show($id)
    {
        $vente  = DB::table('ventes')->find($id);
        $lignes = DB::table('lignes_vente')->where('vente_id', $id)->get();
        return response()->json(['data' => array_merge((array)$vente, ['lignes' => $lignes])]);
    }

    public function update(Request $request, $id) {}
    public function destroy($id) {}
}
