<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    private string $token;
    private string $apiUrl;

    public function __construct()
    {
        $this->token  = config('services.telegram.bot_token', '');
        $this->apiUrl = "https://api.telegram.org/bot{$this->token}";
    }

    // ─── Envoyer un message via Telegram Bot API ──────────────────
    public static function envoyerMessage(string $chatId, string $texte): bool
    {
        $token = config('services.telegram.bot_token', '');
        if (empty($token)) return false;

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $texte,
                'parse_mode' => 'HTML',
            ]);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Telegram send error: ' . $e->getMessage());
            return false;
        }
    }

    // ─── Webhook Telegram ─────────────────────────────────────────
    // POST /api/telegram/webhook
    // Reçoit les messages des commerçants et lie leur chat_id à leur numéro.
    //
    // Le commerçant envoie son numéro au bot :
    //   "Mon numéro est 66123456" ou juste "66123456"
    // Le bot répond et stocke le chat_id dans la table boutiques.
    public function webhook(Request $request)
    {
        try {
            $update  = $request->all();
            $message = $update['message'] ?? $update['edited_message'] ?? null;

            if (!$message) return response()->json(['ok' => true]);

            $chatId = (string) $message['chat']['id'];
            $texte  = trim($message['text'] ?? '');
            $prenom = $message['from']['first_name'] ?? 'ami';

            // Commande /start — accueil
            if (str_starts_with($texte, '/start')) {
                self::envoyerMessage($chatId,
                    "👋 Bonjour <b>{$prenom}</b> !\n\n"
                  . "Je suis le bot de support <b>Sawik</b> 🏪\n\n"
                  . "Pour lier votre compte, envoyez simplement votre numéro de téléphone enregistré dans Sawik.\n\n"
                  . "<i>Exemple : 66123456</i>"
                );
                return response()->json(['ok' => true]);
            }

            // Extraire le numéro de téléphone du message
            $numero = preg_replace('/[^0-9]/', '', $texte);

            if (strlen($numero) >= 8) {
                // Chercher la boutique par ce numéro (avec ou sans préfixe pays)
                $boutique = Boutique::where('telephone', $numero)
                    ->orWhere('telephone', '+235' . $numero)
                    ->orWhere('telephone', '235' . $numero)
                    ->first();

                if ($boutique) {
                    $boutique->update(['telegram_chat_id' => $chatId]);
                    self::envoyerMessage($chatId,
                        "✅ <b>Compte lié avec succès !</b>\n\n"
                      . "Boutique : <b>{$boutique->nom}</b>\n"
                      . "Propriétaire : {$boutique->proprietaire}\n\n"
                      . "Dorénavant, vous recevrez vos codes OTP ici directement. 🔐"
                    );
                } else {
                    self::envoyerMessage($chatId,
                        "❌ Numéro <b>{$numero}</b> introuvable dans Sawik.\n\n"
                      . "Vérifiez que c'est le même numéro utilisé à l'inscription.\n"
                      . "Besoin d'aide ? Contactez le support : +23599966622"
                    );
                }
            } else {
                self::envoyerMessage($chatId,
                    "📱 Envoyez votre numéro de téléphone Sawik pour lier votre compte.\n\n"
                  . "<i>Exemple : 66123456</i>"
                );
            }

        } catch (\Exception $e) {
            Log::error('Telegram webhook error: ' . $e->getMessage());
        }

        return response()->json(['ok' => true]);
    }

    // ─── Enregistrer le webhook auprès de Telegram ────────────────
    // GET /api/telegram/set-webhook  (à appeler une seule fois)
    public function setWebhook(Request $request)
    {
        $appUrl      = config('app.url');
        $webhookUrl  = "{$appUrl}/api/telegram/webhook";

        $response = Http::post("{$this->apiUrl}/setWebhook", [
            'url'             => $webhookUrl,
            'allowed_updates' => ['message'],
            'drop_pending_updates' => true,
        ]);

        return response()->json([
            'webhook_url' => $webhookUrl,
            'telegram'    => $response->json(),
        ]);
    }
}
