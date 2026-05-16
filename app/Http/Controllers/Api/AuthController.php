<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'telephone'    => 'required|string',
            'mot_de_passe' => 'required|string',
        ]);

        $user = User::where('telephone', $credentials['telephone'])
                    ->where('actif', 1)
                    ->first();

        if (!$user || !Hash::check($credentials['mot_de_passe'], $user->mot_de_passe_hash)) {
            return response()->json(['message' => 'Identifiants incorrects'], 401);
        }

        $token = JWTAuth::fromUser($user);

        $user->update(['derniere_connexion' => now()]);

        return response()->json([
            'token'      => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user'       => [
                'id'        => $user->id,
                'nom'       => $user->nom,
                'prenom'    => $user->prenom,
                'telephone' => $user->telephone,
                'role'      => $user->role,
            ],
        ]);
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Déconnecté avec succès']);
    }

    public function me()
    {
        return response()->json(auth()->user());
    }
}
