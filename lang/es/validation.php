<?php

return [
    'accepted'             => 'El campo :attribute debe ser aceptado.',
    'current_password'     => 'La contraseña es incorrecta.',
    'confirmed'            => 'La confirmación de :attribute no coincide.',
    'email'                => 'El campo :attribute debe ser una dirección de correo válida.',
    'in'                   => ':attribute no es válido.',
    'max'                  => [
        'string'  => 'El campo :attribute no debe ser mayor que :max caracteres.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
    ],
    'min'                  => [
        'string'  => 'El campo :attribute debe contener al menos :min caracteres.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
    ],
    'required'             => 'El campo :attribute es obligatorio.',
    'size'                 => [
        'string'  => 'El campo :attribute debe contener :size caracteres.',
        'numeric' => 'El campo :attribute debe ser :size.',
    ],
    'string'               => 'El campo :attribute debe ser una cadena de caracteres.',
    'unique'               => 'El :attribute ya ha sido registrado.',

    // Mensajes de un campo en particular (sirven en web y API por igual).
    'custom' => [
        // Cotizaciones y ventas: el sobreprecio solo sube el precio.
        'items.*.sobreprecio' => [
            'min' => 'El sobreprecio solo acepta cantidades positivas. Para bajar el precio usa el campo Descuento.',
        ],
    ],

    // Nombres legibles de los campos
    'attributes' => [
        'name'                  => 'nombre',
        'email'                 => 'correo electrónico',
        'password'              => 'contraseña',
        'current_password'      => 'contraseña actual',
        'password_confirmation' => 'confirmación de contraseña',
        'code'                  => 'código',
        'confirm'               => 'confirmación',
        'banned_reason'         => 'motivo',
    ],
];
