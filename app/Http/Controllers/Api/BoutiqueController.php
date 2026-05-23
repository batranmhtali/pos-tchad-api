<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class BoutiqueController extends Controller
{
    public function inscrire(Request $request)
    {
        try {
            $request->validate([
                'nom'          => 'required|string|max:100',
                'proprietaire' => 'required|string|max:100',
                'telephone'    => 'required|string|unique:boutiques,telephone',
                'mot_de_passe' => 'required|string|min:4',
                'ville'        => 'nullable|string',
                'email'        => 'nullable|email',
            ]);

            $boutique = Boutique::create([
                'nom'              => $request->nom,
                'proprietaire'     => $request->proprietaire,
                'telephone'        => $request->telephone,
                'email'            => $request->email,
                'ville'            => $request->ville ?? "N'Djamena",
                'mot_de_passe_hash'=> Hash::make($request->mot_de_passe),
                'token_api'        => Boutique::genererToken(),
                'plan'             => 'essai',
                'essai_debut'      => now(),
                'essai_fin'        => now()->addMonths(2),
                'abonnement_actif' => true,
                'prix_mensuel'     => 5000,
            ]);

            return response()->json([
                'message'  => 'Boutique creee! 2 mois essai gratuit.',
                'boutique' => [
                    'id'          => $boutique->id,
                    'nom'         => $boutique->nom,
                    'proprietaire'=> $boutique->proprietaire,
                    'telephone'   => $boutique->telephone,
                    'ville'       => $boutique->ville,
                    'plan'        => $boutique->plan,
                    'essai_fin'   => $boutique->essai_fin->format('d/m/Y'),
                    'jours_essai' => $boutique->joursEssaiRestants(),
                ],
                'token' => $boutique->token_api,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Donnees invalides', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Inscription error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function connexion(Request $request)
    {
        try {
            $request->validate([
                'telephone'    => 'required|string',
                'mot_de_passe' => 'required|string',
            ]);

            $boutique = Boutique::where('telephone', $request->telephone)
                ->where('actif', true)->first();

            if (!$boutique || !$boutique->verifierMotDePasse($request->mot_de_passe)) {
                return response()->json(['message' => 'Telephone ou mot de passe incorrect'], 401);
            }

            $joursRestants = $boutique->joursEssaiRestants();

            return response()->json([
                'token'    => $boutique->token_api,
                'boutique' => [
                    'id'               => $boutique->id,
                    'nom'              => $boutique->nom,
                    'proprietaire'     => $boutique->proprietaire,
                    'telephone'        => $boutique->telephone,
                    'ville'            => $boutique->ville,
                    'plan'             => $boutique->plan,
                    'abonnement_valide'=> $boutique->abonnementValide(),
                    'jours_essai'      => $joursRestants,
                    'essai_fin'        => $boutique->essai_fin?->format('d/m/Y'),
                ],
                'alerte' => $joursRestants <= 15 && $boutique->plan === 'essai'
                    ? "$joursRestants jours d essai restants"
                    : null,
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function profil(Request $request)
    {
        $boutique = $request->boutique;
        return response()->json([
            'id'               => $boutique->id,
            'nom'              => $boutique->nom,
            'proprietaire'     => $boutique->proprietaire,
            'telephone'        => $boutique->telephone,
            'ville'            => $boutique->ville,
            'plan'             => $boutique->plan,
            'abonnement_valide'=> $boutique->abonnementValide(),
            'jours_essai'      => $boutique->joursEssaiRestants(),
            'essai_fin'        => $boutique->essai_fin?->format('d/m/Y'),
        ]);
    }
}
