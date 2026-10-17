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

    /*
    | Tienda en línea (sitio público en React que lee /api/v1/tienda).
    | avisar_a: correos (separados por coma) que reciben cada solicitud web.
    */
    'tienda' => [
        'whatsapp' => env('MB_TIENDA_WHATSAPP', ''),
        'correo' => env('MB_TIENDA_CORREO', ''),
        'avisar_a' => env('MB_TIENDA_AVISAR_A', ''),
    ],

    /*
    | Cuentas para transferencia. Se elige el bloque según el IVA del documento:
    | con IVA (requiere factura) va la cuenta de Anahí Téllez; sin IVA van las
    | cuentas de Gabriela Díaz García. El bloque sin IVA admite varias cuentas.
    | Ver App\Support\DatosBancarios, que resuelve cuál imprimir.
    */
    'bancos' => [

        // Con IVA → Anahí Téllez (una sola cuenta).
        'con_iva' => [
            [
                'nombre' => env('MB_BANCO_IVA', ''),
                'beneficiario' => env('MB_BANCO_IVA_BENEFICIARIO', ''),
                'cuenta' => env('MB_BANCO_IVA_CUENTA', ''),
                'clabe' => env('MB_BANCO_IVA_CLABE', ''),
                'tarjeta' => env('MB_BANCO_IVA_TARJETA', ''),
            ],
        ],

        // Sin IVA → Gabriela Díaz García (puede llevar varias cuentas).
        'sin_iva' => [
            [
                'nombre' => env('MB_BANCO_SIVA_A', ''),
                'beneficiario' => env('MB_BANCO_SIVA_A_BENEFICIARIO', ''),
                'cuenta' => env('MB_BANCO_SIVA_A_CUENTA', ''),
                'clabe' => env('MB_BANCO_SIVA_A_CLABE', ''),
                'tarjeta' => env('MB_BANCO_SIVA_A_TARJETA', ''),
            ],
            [
                'nombre' => env('MB_BANCO_SIVA_B', ''),
                'beneficiario' => env('MB_BANCO_SIVA_B_BENEFICIARIO', ''),
                'cuenta' => env('MB_BANCO_SIVA_B_CUENTA', ''),
                'clabe' => env('MB_BANCO_SIVA_B_CLABE', ''),
                'tarjeta' => env('MB_BANCO_SIVA_B_TARJETA', ''),
            ],
        ],
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
