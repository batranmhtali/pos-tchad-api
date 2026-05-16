<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function index()
    {
        $clients = DB::table('clients')
            ->where('actif', 1)
            ->orderBy('nom')
            ->get();
        return response()->json(['data' => $clients]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom'           => 'required|string|max:255',
            'prenom'        => 'nullable|string',
            'telephone'     => 'nullable|string|unique:clients',
            'quartier'      => 'nullable|string',
            'limite_credit' => 'nullable|numeric|min:0',
        ]);
        $id = DB::table('clients')->insertGetId(array_merge($data, [
            'solde_credit' => 0,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]));
        return response()->json(['data' => DB::table('clients')->find($id)], 201);
    }

    public function show($id)
    {
        $client = DB::table('clients')->find($id);
        if (!$client) return response()->json(['message' => 'Client introuvable'], 404);
        return response()->json(['data' => $client]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->only(['nom','prenom','telephone','quartier','limite_credit','notes']);
        DB::table('clients')->where('id', $id)->update(
            array_merge($data, ['updated_at' => now()])
        );
        return response()->json(['data' => DB::table('clients')->find($id)]);
    }

    public function destroy($id)
    {
        DB::table('clients')->where('id', $id)->update(['actif' => 0]);
        return response()->json(['message' => 'Client archivé']);
    }
}
