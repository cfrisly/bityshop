<?php

namespace App;

use Vanilo\Product\Models\ProductProxy;

class User extends \Konekt\AppShell\Models\User
{
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
