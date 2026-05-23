<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Boutique extends Model
{
    protected $fillable = [
        'nom', 'proprietaire', 'telephone', 'email', 'ville', 'pays',
        'mot_de_passe_hash', 'token_api', 'actif', 'plan',
        'essai_debut', 'essai_fin', 'abonnement_debut', 'abonnement_fin',
        'abonnement_actif', 'prix_mensuel',
    ];

    protected $hidden = ['mot_de_passe_hash', 'token_api'];

    protected $casts = [
        'actif' => 'boolean',
        'abonnement_actif' => 'boolean',
        'essai_debut' => 'datetime',
        'essai_fin' => 'datetime',
        'abonnement_debut' => 'datetime',
        'abonnement_fin' => 'datetime',
    ];

    public function essaiActif(): bool
    {
        return $this->plan === 'essai' && $this->essai_fin && now()->lt($this->essai_fin);
    }

    public function abonnementValide(): bool
    {
        if ($this->essaiActif()) return true;
        return $this->abonnement_actif && $this->abonnement_fin && now()->lt($this->abonnement_fin);
    }

    public function joursEssaiRestants(): int
    {
        if (!$this->essai_fin) return 0;
        return max(0, now()->diffInDays($this->essai_fin, false));
    }

    public function verifierMotDePasse(string $motDePasse): bool
    {
        return Hash::check($motDePasse, $this->mot_de_passe_hash);
    }

    public static function genererToken(): string
    {
        return 'sawik_' . bin2hex(random_bytes(32));
    }

    public function utilisateurs() { return $this->hasMany(Utilisateur::class); }
    public function produits() { return $this->hasMany(Produit::class); }
    public function clients() { return $this->hasMany(Client::class); }
    public function ventes() { return $this->hasMany(Vente::class); }
}
