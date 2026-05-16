<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProduitController extends Controller
{
    public function index()
    {
        $produits = DB::table('produits')
            ->leftJoin('categories', 'produits.categorie_id', '=', 'categories.id')
            ->leftJoin('stock', 'stock.produit_id', '=', 'produits.id')
            ->select('produits.*', 'categories.nom as categorie_nom',
                     'stock.quantite_actuelle', 'stock.seuil_alerte')
            ->where('produits.actif', 1)
            ->orderBy('produits.nom')
            ->get();

        return response()->json(['data' => $produits]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom'        => 'required|string|max:255',
            'prix_vente' => 'required|numeric|min:0',
            'prix_achat' => 'nullable|numeric|min:0',
            'unite'      => 'nullable|string',
            'code_barre' => 'nullable|string|unique:produits',
        ]);

        $id = DB::table('produits')->insertGetId(array_merge($data, [
            'created_at' => now(), 'updated_at' => now(),
        ]));

        DB::table('stock')->insert([
            'produit_id'        => $id,
            'quantite_actuelle' => 0,
            'seuil_alerte'      => 5,
            'updated_at'        => now(),
        ]);

        return response()->json(['data' => DB::table('produits')->find($id)], 201);
    }

    public function show($id)
    {
        $produit = DB::table('produits')->find($id);
        if (!$produit) return response()->json(['message' => 'Produit introuvable'], 404);
        return response()->json(['data' => $produit]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'nom'        => 'sometimes|string|max:255',
            'prix_vente' => 'sometimes|numeric|min:0',
            'prix_achat' => 'sometimes|numeric|min:0',
        ]);
        DB::table('produits')->where('id', $id)->update(
            array_merge($data, ['updated_at' => now()])
        );
        return response()->json(['data' => DB::table('produits')->find($id)]);
    }

    public function destroy($id)
    {
        DB::table('produits')->where('id', $id)->update(['actif' => 0]);
        return response()->json(['message' => 'Produit archivé']);
    }
}
