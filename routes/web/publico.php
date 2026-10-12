<?php

use App\Http\Controllers\ConsultaPublicaController;
use App\Http\Controllers\FichaEquipoPublicaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Consulta pública (sin sesión)
|--------------------------------------------------------------------------
| Lo que abre el cliente al escanear el QR del PDF. Sin middleware de auth
| a propósito: el cliente no tiene cuenta en el sistema.
|
| La dirección lleva un UUID, no el id del documento, así que no se puede
| llegar a la cotización de otro cliente probando números.
*/

Route::prefix('consulta')->name('publico.')->group(function () {
    Route::get('/cotizacion/{token}', [ConsultaPublicaController::class, 'cotizacion'])
        ->name('cotizacion');
    Route::get('/cotizacion/{token}/pdf', [ConsultaPublicaController::class, 'cotizacionPdf'])
        ->name('cotizacion.pdf');

    Route::get('/venta/{token}', [ConsultaPublicaController::class, 'venta'])
        ->name('venta');
    Route::get('/venta/{token}/pdf', [ConsultaPublicaController::class, 'ventaPdf'])
        ->name('venta.pdf');

    // Recibo de un pago concreto de esa venta.
    Route::get('/venta/{token}/recibo/{cobro}', [ConsultaPublicaController::class, 'recibo'])
        ->name('venta.recibo');
})->where(['token' => '[0-9a-fA-F-]{36}']);

/*
|--------------------------------------------------------------------------
| Ficha de una pieza (el QR pegado al equipo)
|--------------------------------------------------------------------------
| La dirección es corta a propósito: es lo que va impreso en la etiqueta y
| lo que teclea una pistola lectora. No lleva nada comercial, así que no
| importa que se pueda escribir a mano.
*/
Route::get('/equipo/{codigo}', FichaEquipoPublicaController::class)
    ->name('publico.equipo')
    ->where('codigo', '[A-Za-z]{2,6}-[0-9]{4,10}');

/*
|--------------------------------------------------------------------------
| Certificado de la CA local (solo en desarrollo)
|--------------------------------------------------------------------------
| Los celulares lo instalan una vez para confiar en https://<ip-del-servidor>
| y así poder usar la cámara en la venta rápida. Se sirve con el tipo MIME
| que iOS necesita para ofrecer instalarlo como perfil.
*/
if (app()->environment('local')) {
    Route::get('/certificado-local', function () {
        $ruta = public_path('medibuy-ca.crt');
        abort_unless(is_file($ruta), 404);

        return response()->file($ruta, ['Content-Type' => 'application/x-x509-ca-cert']);
    })->name('publico.certificado');
}
