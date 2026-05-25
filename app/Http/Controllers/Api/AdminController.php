<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use App\Models\Abonnement;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    private function verifierAdmin()
    {
        $user = auth()->user();
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Accès refusé');
        }
    }

    public function listeUtilisateurs()
    {
        $this->verifierAdmin();

        $utilisateurs = Utilisateur::with('abonnement')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($u) {
                return [
                    'id'         => $u->id,
                    'nom'        => $u->nom,
                    'prenom'     => $u->prenom,
                    'telephone'  => $u->telephone,
                    'role'       => $u->role,
                    'actif'      => $u->actif,
                    'created_at' => $u->created_at,
                    'abonnement' => $u->abonnement ? [
                        'plan'       => $u->abonnement->plan,
                        'statut'     => $u->abonnement->statut,
                        'date_debut' => $u->abonnement->date_debut,
                        'date_fin'   => $u->abonnement->date_fin,
                        'notes'      => $u->abonnement->notes,
                    ] : null,
                ];
            });

        return response()->json(['utilisateurs' => $utilisateurs]);
    }

    public function modifierAbonnement(Request $request, $id)
    {
        $this->verifierAdmin();

        $request->validate([
            'plan'       => 'required|in:Gratuit,Essai,Pro',
            'statut'     => 'required|in:Actif,Essai,Expiré,Suspendu',
            'date_debut' => 'nullable|date',
            'date_fin'   => 'nullable|date',
            'notes'      => 'nullable|string|max:255',
        ]);

        $utilisateur = Utilisateur::findOrFail($id);

        Abonnement::updateOrCreate(
            ['utilisateur_id' => $utilisateur->id],
            [
                'plan'       => $request->plan,
                'statut'     => $request->statut,
                'date_debut' => $request->date_debut,
                'date_fin'   => $request->date_fin,
                'notes'      => $request->notes,
            ]
        );

        return response()->json(['message' => 'Abonnement mis à jour']);
    }

    public function suspendreUtilisateur($id)
    {
        $this->verifierAdmin();

        $utilisateur = Utilisateur::findOrFail($id);
        $utilisateur->actif = !$utilisateur->actif;
        $utilisateur->save();

        return response()->json([
            'message' => $utilisateur->actif ? 'Utilisateur activé' : 'Utilisateur suspendu',
            'actif'   => $utilisateur->actif,
        ]);
    }

    public function statistiques()
    {
        $this->verifierAdmin();

        return response()->json([
            'total'    => Utilisateur::count(),
            'actifs'   => Utilisateur::where('actif', true)->count(),
            'pro'      => Abonnement::where('plan', 'Pro')->where('statut', 'Actif')->count(),
            'essai'    => Abonnement::where('statut', 'Essai')->count(),
            'expires'  => Abonnement::where('statut', 'Expiré')->count(),
        ]);
    }
}
