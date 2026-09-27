<?php

namespace App;

class User extends \Konekt\AppShell\Models\User
{
    public function orders(){
        return $this->hasMany(
            \Vanilo\Order\Models\OrderProxy::modelClass(),
            'user_id'
        );
    }
}
