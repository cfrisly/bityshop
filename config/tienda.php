<?php

/*
|--------------------------------------------------------------------------
| Datos de la tienda que se muestran en el carrito
|--------------------------------------------------------------------------
| Todo se puede cambiar desde el archivo .env sin tocar el código.
*/

return [

    'envio' => [
        // Costo de envío que se muestra en el carrito (Q)
        'costo' => (float) env('ENVIO_COSTO', 15),
        // A partir de este subtotal el envío es gratis (0 = nunca es gratis)
        'gratis_desde' => (float) env('ENVIO_GRATIS_DESDE', 1000),
        // Días hábiles (lunes a viernes) para la entrega a domicilio
        'dias_min' => (int) env('ENVIO_DIAS_MIN', 1),
        'dias_max' => (int) env('ENVIO_DIAS_MAX', 3),
        // Texto de la zona de entrega cuando el cliente no tiene dirección guardada
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
