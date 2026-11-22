<?php

/*
|--------------------------------------------------------------------------
| Datos de la tienda que se muestran en el carrito
|--------------------------------------------------------------------------
| Todo se puede cambiar desde el archivo .env sin tocar el código.
*/

return [

    'envio' => [
        // Las tarifas, couriers y días de entrega se configuran en el admin:
        //   Transportistas, Zonas (departamentos) y Métodos de envío.
        // Texto de ubicación cuando el cliente no tiene dirección guardada
        'zona' => env('ENVIO_ZONA', 'Guatemala'),
    ],

    'recoger' => [
        // Lugar para recoger gratis. Si se deja vacío, la opción no aparece en el carrito
        'lugar' => env('RECOGER_LUGAR'),
        'horario' => env('RECOGER_HORARIO', 'de 9:00 a 17:00'),
        // Días hábiles para que el pedido esté listo en tienda
        'dias' => (int) env('RECOGER_DIAS', 1),
    ],

];
