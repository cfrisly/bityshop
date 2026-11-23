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

        // Al borrar una promoción en el admin se borran antes sus reglas, acciones y cupones
        // (la base de datos no dejaba borrarla mientras los tuviera)
        \Vanilo\Promotion\Models\PromotionProxy::modelClass()::deleting(function ($promotion) {
            $promotion->rules()->delete();
            $promotion->actions()->delete();
            $promotion->coupons()->delete();
        });

        // Correo de verificación de la cuenta, con el nombre de la tienda y en su idioma
        \Illuminate\Auth\Notifications\VerifyEmail::toMailUsing(function ($user, string $url) {
            $tienda = config('app.name');

            return (new \Illuminate\Notifications\Messages\MailMessage())
                ->subject(__('Confirma tu correo en :tienda', ['tienda' => $tienda]))
                ->greeting(__('¡Hola, :nombre!', ['nombre' => \Illuminate\Support\Str::before((string) $user->name, ' ') ?: $user->name]))
                ->line(__('Gracias por registrarte en :tienda. Para activar tu cuenta, confirma que este correo es tuyo.', ['tienda' => $tienda]))
                ->action(__('Verificar mi correo'), $url)
                ->line(__('El enlace vence en :min minutos. Si vence, puedes pedir otro desde la tienda.', ['min' => config('auth.verification.expire', 60)]))
                ->line(__('Si no creaste una cuenta en :tienda, puedes ignorar este correo.', ['tienda' => $tienda]))
                ->salutation(__('El equipo de :tienda', ['tienda' => $tienda]));
        });

        // Promociones "Exclusivas": si aplica una, no se suman las demás
        $this->app->bind(\Vanilo\Foundation\Listeners\CalculatePromotions::class, \App\Listeners\CalculatePromotions::class);

        // Menú del admin: Configuración → Logos de temporada
        $this->app->booted(function () {
            $menu = \Konekt\Menu\Facades\Menu::get('appshell');
            if ($menu && $settings = $menu->getItem('settings_group')) {
                $settings->addSubItem('logos', __('Logos de temporada'), ['route' => 'tienda.admin.logo.index'])
                    ->data('icon', 'image')
                    ->activateOnUrls('/admin/logo*')
                    ->allowIfUserCan('list logos');
            }
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // El idioma se aplica antes de que arranquen AppShell y Vanilo, porque arman
        // el menú del admin y otras etiquetas al iniciar (ver también EstablecerIdioma)
        $this->app->booting(function () {
            $idioma = \App\Support\Ajustes::idiomaGuardado();
            $this->app->setLocale($idioma);
            \Illuminate\Support\Carbon::setLocale($idioma);
        });
    }
}
