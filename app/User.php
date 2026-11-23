<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Vanilo\Product\Models\ProductProxy;

/**
 * Usuario de la tienda. Implementa MustVerifyEmail: al registrarse recibe un
 * correo para confirmar que su dirección es real (ver email_verified_at).
 */
class User extends \Konekt\AppShell\Models\User implements MustVerifyEmail
{
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Los usuarios que crea un administrador desde el panel quedan verificados
        static::creating(function (self $user) {
            if (request()->is('admin/*') && $user->email_verified_at === null) {
                $user->email_verified_at = now();
            }
        });
    }

    public function orders(){
        return $this->hasMany(
            \Vanilo\Order\Models\OrderProxy::modelClass(),
            'user_id'
        );
    }

    public function favoriteProducts(){
        return $this->belongsToMany(ProductProxy::modelClass(), 'favorites', 'user_id', 'product_id')
            ->withTimestamps();
    }
}
