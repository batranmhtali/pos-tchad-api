<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Abonnement;
use App\Models\Boutique;
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

        $utilisateurs = User::with('abonnement')
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

        $utilisateur = User::findOrFail($id);

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

        $utilisateur = User::findOrFail($id);
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
            'total'    => User::count(),
            'actifs'   => User::where('actif', true)->count(),
            'pro'      => Abonnement::where('plan', 'Pro')->where('statut', 'Actif')->count(),
            'essai'    => Abonnement::where('statut', 'Essai')->count(),
            'expires'  => Abonnement::where('statut', 'Expiré')->count(),
        ]);
    }


    public function ajouterUtilisateur(Request $request)
    {
        $this->verifierAdmin();

        $request->validate([
            'nom'          => 'required|string|max:100',
            'prenom'       => 'nullable|string|max:100',
            'telephone'    => 'required|string|unique:utilisateurs,telephone',
            'mot_de_passe' => 'required|string|min:4',
            'role'         => 'in:user,admin',
        ]);

        $user = User::create([
            'nom'              => $request->nom,
            'prenom'           => $request->prenom ?? '',
            'telephone'        => $request->telephone,
            'mot_de_passe_hash'=> \Illuminate\Support\Facades\Hash::make($request->mot_de_passe),
            'role'             => $request->role ?? 'user',
            'actif'            => true,
        ]);

        return response()->json(['message' => 'Utilisateur créé', 'utilisateur' => $user], 201);
    }

    public function changerMotDePasse(Request $request, $id)
    {
        $this->verifierAdmin();

        $request->validate([
            'mot_de_passe' => 'required|string|min:4',
        ]);

        $user = User::findOrFail($id);
        $user->mot_de_passe_hash = \Illuminate\Support\Facades\Hash::make($request->mot_de_passe);
        $user->save();

        return response()->json(['message' => 'Mot de passe modifié']);
    }

    // ─── Admin Boutiques SaaS ─────────────────────────────────────

    public function listeBoutiques()
    {
        $this->verifierAdmin();

        $boutiques = Boutique::orderBy('created_at', 'desc')->get()->map(function ($b) {
            return [
                'id'               => $b->id,
                'nom'              => $b->nom,
                'proprietaire'     => $b->proprietaire,
                'telephone'        => $b->telephone,
                'email'            => $b->email,
                'ville'            => $b->ville ?? 'N\'Djamena',
                'plan'             => $b->plan,
                'actif'            => $b->actif,
                'abonnement_valide'=> $b->abonnementValide(),
                'essai_actif'      => $b->essaiActif(),
                'jours_restants'   => $b->joursEssaiRestants(),
                'essai_fin'        => $b->essai_fin?->format('Y-m-d'),
                'abonnement_fin'   => $b->abonnement_fin?->format('Y-m-d'),
                'abonnement_actif' => $b->abonnement_actif,
                'est_principale'   => $b->est_principale ?? true,
                'telegram_lie'     => !empty($b->telegram_chat_id),
                'created_at'       => $b->created_at->format('Y-m-d'),
            ];
        });

        return response()->json(['boutiques' => $boutiques]);
    }

    public function statistiquesBoutiques()
    {
        $this->verifierAdmin();

        $boutiques = Boutique::all();
        $total     = $boutiques->count();
        $actives   = $boutiques->filter(fn($b) => $b->abonnementValide())->count();
        $essai     = $boutiques->filter(fn($b) => $b->essaiActif())->count();
        $pro       = $boutiques->where('plan', 'pro')->where('abonnement_actif', true)->count();
        $expires   = $boutiques->filter(fn($b) => !$b->abonnementValide() && $b->actif)->count();
        $telegram  = $boutiques->filter(fn($b) => !empty($b->telegram_chat_id))->count();
        $revenus   = $pro * 5000;

        return response()->json(compact('total','actives','essai','pro','expires','telegram','revenus'));
    }

    public function modifierAbonnementBoutique(Request $request, $id)
    {
        $this->verifierAdmin();

        $boutique = Boutique::findOrFail($id);

        $data = [];
        if ($request->has('plan'))             $data['plan']             = $request->plan;
        if ($request->has('essai_fin'))        $data['essai_fin']        = $request->essai_fin;
        if ($request->has('abonnement_fin'))   $data['abonnement_fin']   = $request->abonnement_fin;
        if ($request->has('abonnement_actif')) $data['abonnement_actif'] = $request->abonnement_actif;

        $boutique->update($data);

        return response()->json(['message' => 'Boutique mise à jour', 'boutique' => [
            'id'               => $boutique->id,
            'plan'             => $boutique->fresh()->plan,
            'abonnement_valide'=> $boutique->fresh()->abonnementValide(),
            'jours_restants'   => $boutique->fresh()->joursEssaiRestants(),
        ]]);
    }

    public function suspendreBoutique($id)
    {
        $this->verifierAdmin();

        $boutique = Boutique::findOrFail($id);
        $boutique->actif = !$boutique->actif;
        $boutique->save();

        return response()->json([
            'message' => $boutique->actif ? 'Boutique activée' : 'Boutique suspendue',
            'actif'   => $boutique->actif,
        ]);
    }

    public function changerMotDePasseBoutique(Request $request, $id)
    {
        $this->verifierAdmin();

        $request->validate(['mot_de_passe' => 'required|string|min:4']);

        $boutique = Boutique::findOrFail($id);
        $boutique->mot_de_passe_hash = \Illuminate\Support\Facades\Hash::make($request->mot_de_passe);
        $boutique->save();

        return response()->json(['message' => 'Mot de passe modifié']);
    }
}
