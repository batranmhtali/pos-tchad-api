<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    protected $table = 'utilisateurs';

    protected $fillable = [
        'nom', 'prenom', 'telephone', 'role',
        'mot_de_passe_hash', 'actif', 'derniere_connexion',
    ];

    protected $hidden = ['mot_de_passe_hash'];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return ['role' => $this->role];
    }
}
