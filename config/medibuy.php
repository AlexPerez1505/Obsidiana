<?php

/*
|--------------------------------------------------------------------------
| Datos de la empresa para los documentos
|--------------------------------------------------------------------------
| Lo que sale impreso en cotizaciones, ventas y facturas.
|
| Los datos bancarios se leen del .env a propósito: son sensibles y el .env
| no se sube al repositorio. Si cambia una cuenta, se cambia ahí y listo,
| sin tocar código ni volver a desplegar plantillas.
*/

return [

    'empresa' => [
        'nombre' => env('MB_NOMBRE', 'Grupo MediBuy'),
        'giro' => env('MB_GIRO', 'Equipo médico'),
        'rfc' => env('MB_RFC', ''),
        'regimen' => env('MB_REGIMEN', ''),
        'ubicacion' => env('MB_UBICACION', ''),
        'web' => env('MB_WEB', ''),
    ],

    // Bloque de contacto del pie de página.
    'contacto' => [
        'nombre' => env('MB_CONTACTO_NOMBRE', ''),
        'cargo' => env('MB_CONTACTO_CARGO', ''),
        'telefono' => env('MB_CONTACTO_TEL', ''),
        'correo' => env('MB_CONTACTO_CORREO', ''),
    ],

    // A dónde manda el cliente su comprobante de pago.
    'comprobantes' => [
        'correo' => env('MB_COMPROBANTES_CORREO', ''),
        'whatsapp' => env('MB_COMPROBANTES_WHATSAPP', ''),
    ],

    'banco' => [
        'nombre' => env('MB_BANCO', ''),
        'beneficiario' => env('MB_BANCO_BENEFICIARIO', ''),
        'cuenta' => env('MB_BANCO_CUENTA', ''),
        'clabe' => env('MB_BANCO_CLABE', ''),
        'tarjeta' => env('MB_BANCO_TARJETA', ''),
    ],

    /*
    | Términos y condiciones impresos en el documento. No son sensibles, así
    | que viven aquí: se editan sin tocar la plantilla del PDF.
    */
    'terminos' => [
        'Todos los pagos deberán realizarse puntualmente según el calendario acordado.',
        'En caso de retraso en el pago, se aplicará un cargo moratorio del 5% mensual sobre el monto vencido.',
        'El equipo permanecerá como propiedad de Grupo MediBuy hasta la liquidación total del pago.',
        'La garantía del equipo es de 6 meses a partir de la fecha de entrega.',
        'Los precios pueden cambiar sin previo aviso. Los productos están sujetos a disponibilidad.',
        'Cualquier ajuste a las condiciones de pago deberá ser autorizado por escrito por la empresa.',
        'En caso de requerir factura, el monto será más IVA. Para su emisión, será necesario que nos proporcione sus datos fiscales completos.',
    ],

];
