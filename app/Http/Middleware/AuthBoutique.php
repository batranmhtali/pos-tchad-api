<?php

namespace App\Http\Middleware;

use App\Models\Boutique;
use Closure;
use Illuminate\Http\Request;

class AuthBoutique
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken() ?? $request->header('X-Boutique-Token');

        if (!$token) {
            return response()->json(['message' => 'Token manquant'], 401);
        }

        $boutique = Boutique::where('token_api', $token)->where('actif', true)->first();

        if (!$boutique) {
            return response()->json(['message' => 'Token invalide'], 401);
        }

        if (!$boutique->abonnementValide()) {
            return response()->json([
                'message' => 'Abonnement expire',
                'code'    => 'ABONNEMENT_EXPIRE',
            ], 403);
        }

        $request->boutique    = $boutique;
        $request->boutique_id = $boutique->id;

        return $next($request);
    }
}
