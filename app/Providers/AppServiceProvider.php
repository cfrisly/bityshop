<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        if (Str::startsWith(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        $this->app->concord->registerModel(\Konekt\User\Contracts\User::class, \App\User::class);

        // Ajustes propios de la tienda (Admin → Configuración → Tienda)
        \App\Support\Ajustes::registrar($this->app['gears.settings_registry']);
        $this->app->afterResolving('appshell.settings_tree_builder', function ($builder) {
            \App\Support\Ajustes::pantallaAdmin($builder);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
