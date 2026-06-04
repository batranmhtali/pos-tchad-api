<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\TelegramController;
use App\Models\Boutique;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
                'plan'                   => 'essai',
                'essai_debut'            => now(),
                'essai_fin'              => now()->addMonths(2),
                'abonnement_actif'       => true,
                'prix_mensuel'           => 5000,
                'proprietaire_telephone' => $request->telephone,
                'est_principale'         => true,
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

    public function lister(Request $request)
    {
        $boutique = $request->boutique;
        $telephone = $boutique->telephone;
        $propTel = $boutique->proprietaire_telephone ?? $telephone;

        $boutiques = Boutique::where(function($q) use ($telephone, $propTel) {
            $q->where('telephone', $telephone)
              ->orWhere('telephone', $propTel)
              ->orWhere('proprietaire_telephone', $telephone)
              ->orWhere('proprietaire_telephone', $propTel);
        })->where('actif', true)->get()->map(function($b) {
            return [
                'id'               => $b->id,
                'nom'              => $b->nom,
                'proprietaire'     => $b->proprietaire,
                'telephone'        => $b->telephone,
                'ville'            => $b->ville,
                'plan'             => $b->plan,
                'abonnement_valide'=> $b->abonnementValide(),
                'jours_essai'      => $b->joursEssaiRestants(),
                'est_principale'   => $b->est_principale ?? true,
                'token'            => $b->token_api,
            ];
        });
        return response()->json(['boutiques' => $boutiques]);
    }

    public function ajouterBoutique(Request $request)
    {
        try {
            $request->validate([
                'nom'   => 'required|string|max:100',
                'ville' => 'nullable|string',
            ]);
            $proprietaire = $request->boutique;
            // Generer un identifiant unique pour la boutique
            $telephone = 'bq_' . $proprietaire->id . '_' . time();
            $nouvelle = Boutique::create([
                'nom'                    => $request->nom,
                'proprietaire'           => $proprietaire->proprietaire,
                'proprietaire_telephone' => $proprietaire->telephone,
                'telephone'              => $telephone,
                'mot_de_passe_hash'      => $proprietaire->mot_de_passe_hash,
                'token_api'              => Boutique::genererToken(),
                'ville'                  => $request->ville ?? $proprietaire->ville,
                'plan'                   => $proprietaire->plan,
                'essai_debut'            => $proprietaire->essai_debut,
                'essai_fin'              => $proprietaire->essai_fin,
                'abonnement_actif'       => true,
                'est_principale'         => false,
                'prix_mensuel'           => 5000,
            ]);
            return response()->json([
                'message'  => 'Nouvelle boutique ajoutee !',
                'boutique' => ['id' => $nouvelle->id, 'nom' => $nouvelle->nom, 'token' => $nouvelle->token_api],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Donnees invalides', 'errors' => $e->errors()], 422);
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

    // ─── OTP : Demande de réinitialisation de mot de passe ───────
    // POST /api/boutiques/otp-demander
    // Génère un OTP côté serveur et l'envoie via Telegram Bot (gratuit, sans limite).
    public function demanderOTP(Request $request)
    {
        try {
            $request->validate(['telephone' => 'required|string']);

            $boutique = Boutique::where('telephone', $request->telephone)
                ->where('actif', true)->first();

            if (!$boutique) {
                // Réponse vague pour éviter l'énumération de comptes
                return response()->json([
                    'message' => 'Si ce numéro existe, un code a été envoyé.',
                ]);
            }

            // Générer un code OTP à 6 chiffres sécurisé
            $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

            // Stocker le HASH du code + expiration 10 minutes
            $boutique->update([
                'otp_code'   => Hash::make($code),
                'otp_expire' => now()->addMinutes(10),
            ]);

            $support = config('app.support_whatsapp', '+23599966622');

            // ─── Canal 1 : Telegram (prioritaire) ──────────────────
            if (!empty($boutique->telegram_chat_id)) {
                $texte =
                    "🔐 <b>Code Sawik</b>\n\n"
                  . "Boutique : <b>{$boutique->nom}</b>\n"
                  . "Code : <b>{$code}</b>\n\n"
                  . "<i>Valable 10 minutes. Ne le partagez jamais.</i>";

                $envoye = TelegramController::envoyerMessage($boutique->telegram_chat_id, $texte);

                if ($envoye) {
                    return response()->json([
                        'message' => 'Code envoyé sur Telegram ✅',
                        'canal'   => 'telegram',
                    ]);
                }
            }

            // ─── Canal 2 : Email (si disponible) ───────────────────
            if (!empty($boutique->email)) {
                try {
                    \Illuminate\Support\Facades\Mail::send([], [], function ($m) use ($boutique, $code) {
                        $m->to($boutique->email)
                          ->subject('Sawik - Code OTP')
                          ->html(
                              "<div style='font-family:sans-serif;max-width:480px;margin:auto;padding:24px'>"
                            . "<h2 style='color:#009688'>🏪 Sawik</h2>"
                            . "<p>Bonjour <strong>{$boutique->proprietaire}</strong>,</p>"
                            . "<p>Votre code de réinitialisation :</p>"
                            . "<div style='background:#f5f5f5;border-radius:10px;padding:20px;"
                            .   "text-align:center;font-size:36px;font-weight:bold;"
                            .   "letter-spacing:12px;color:#009688'>{$code}</div>"
                            . "<p style='color:#888;font-size:13px'>Expire dans <strong>10 minutes</strong>.</p>"
                            . "</div>"
                          );
                    });
                    $emailParts = explode('@', $boutique->email);
                    $local = $emailParts[0];
                    $masque = substr($local, 0, 2) . '***@' . ($emailParts[1] ?? '');
                    return response()->json(['message' => "Code envoyé à $masque", 'canal' => 'email']);
                } catch (\Exception $e) {
                    Log::warning('OTP email fallback failed: ' . $e->getMessage());
                }
            }

            // ─── Canal 3 : Pas de Telegram ni email → support WhatsApp ──
            return response()->json([
                'message'          => 'Compte non lié à Telegram.',
                'sans_telegram'    => true,
                'support_whatsapp' => $support,
                'instruction'      => "Ouvrez Telegram, cherchez @SuppSawik_bot, envoyez votre numéro pour lier votre compte.",
            ], 422);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Numéro requis'], 422);
        } catch (\Exception $e) {
            Log::error('OTP demander error: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }

    // ─── OTP : Vérification + réinitialisation du mot de passe ───
    // POST /api/boutiques/otp-verifier
    public function verifierOTP(Request $request)
    {
        try {
            $request->validate([
                'telephone'         => 'required|string',
                'otp'               => 'required|string|size:6',
                'nouveau_mot_de_passe' => 'required|string|min:4',
            ]);

            $boutique = Boutique::where('telephone', $request->telephone)
                ->where('actif', true)->first();

            if (!$boutique || empty($boutique->otp_code) || empty($boutique->otp_expire)) {
                return response()->json(['message' => 'Aucun code OTP en attente. Recommencez.'], 400);
            }

            // Vérifier expiration
            if (now()->isAfter($boutique->otp_expire)) {
                $boutique->update(['otp_code' => null, 'otp_expire' => null]);
                return response()->json(['message' => 'Code OTP expiré. Recommencez.'], 400);
            }

            // Vérifier le code (comparaison sécurisée contre timing attacks)
            if (!Hash::check($request->otp, $boutique->otp_code)) {
                return response()->json(['message' => 'Code OTP incorrect.'], 400);
            }

            // Réinitialiser le mot de passe et effacer l'OTP
            $boutique->update([
                'mot_de_passe_hash' => Hash::make($request->nouveau_mot_de_passe),
                'otp_code'          => null,
                'otp_expire'        => null,
            ]);

            return response()->json([
                'message' => 'Mot de passe réinitialisé avec succès.',
                'token'   => $boutique->token_api,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Données invalides', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('OTP verifier error: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur serveur'], 500);
        }
    }
}
