<?php

/*
|--------------------------------------------------------------------------
| API REST (/api/v1)
|--------------------------------------------------------------------------
| Dos formas de entrar, misma API:
|
|   - Usuarios desde una app: POST /api/v1/auth/login con correo y
|     contraseña. Reciben un token tipo 'app' que puede lo mismo que el
|     usuario en la web.
|   - Otros sistemas: una llave de integración creada en
|     Configuración → API e integraciones, limitada a los permisos que
|     se le marcaron (y nunca más de lo que puede su dueño).
*/
return [

    // Días sin uso tras los que un token de app deja de servir. Cada uso
    // lo renueva: quien abre la app seguido no tiene que volver a entrar.
    'dias_token_app' => (int) env('API_DIAS_TOKEN_APP', 30),

    // Peticiones por minuto, por token (o por IP si no hay token).
    'limite_por_minuto' => (int) env('API_LIMITE_POR_MINUTO', 120),

    // Intentos de inicio de sesión por minuto, por correo + IP.
    'limite_login_por_minuto' => (int) env('API_LIMITE_LOGIN', 6),

    // Tamaño de página de los listados.
    'por_pagina' => 25,
    'por_pagina_max' => 100,

    // Prefijo visible de las llaves de integración, para reconocerlas
    // si se filtran en un repositorio o en un chat.
    'prefijo_llave' => 'obs_',
];
