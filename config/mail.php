<?php

/*
|--------------------------------------------------------------------------
| Correo
|--------------------------------------------------------------------------
| Se configura en el .env. Opciones de MAIL_MAILER:
|   log   → no envía nada: guarda los correos en storage/logs/laravel.log (para pruebas)
|   smtp  → envía de verdad con MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD
| Ejemplos de MAIL_HOST: smtp.gmail.com (puerto 587, contraseña de aplicación),
| smtp-relay.brevo.com (587), smtp.mailgun.org (587) o el SMTP de tu hosting.
*/

return [

    // MAIL_DRIVER es el nombre antiguo; se sigue leyendo por compatibilidad
    'default' => env('MAIL_MAILER', env('MAIL_DRIVER', 'log')),

    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 587),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => 15,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'no-responder@bityshop.com'),
        'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'BityShop')),
    ],

    'markdown' => [
        // Tema propio: resources/views/vendor/mail/html/themes/bityshop.css
        'theme' => 'bityshop',
        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],

];
