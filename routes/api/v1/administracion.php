<?php

use App\Http\Controllers\Api\V1\Administracion\ActividadController;
use App\Http\Controllers\Api\V1\Administracion\AgendaController;
use App\Http\Controllers\Api\V1\Administracion\MaterialController;
use App\Http\Controllers\Api\V1\Administracion\PermisoController;
use App\Http\Controllers\Api\V1\Administracion\ReporteController;
use App\Http\Controllers\Api\V1\Administracion\RolController;
use App\Http\Controllers\Api\V1\Administracion\UsuarioController;
use App\Http\Controllers\Api\V1\Administracion\VehiculoController;
use App\Http\Controllers\Api\V1\Administracion\ViajeController;
use App\Http\Controllers\Api\V1\Administracion\ViaticoController;
use App\Http\Controllers\Api\V1\Cuenta\CuentaController;
use App\Http\Controllers\Api\V1\Cuenta\NotificacionController;
use App\Http\Controllers\Api\V1\Cuenta\PerfilController;
use App\Http\Controllers\Api\V1\Cuenta\TableroController;
use Illuminate\Support\Facades\Route;

/*
| API v1 — administracion. Lo carga routes/api.php dentro del grupo autenticado
| (auth:sanctum + api.cuenta + throttle:api), con prefijo /api/v1 y
| nombre api.v1.*
|
| Cada ruta pide el mismo permiso que su pantalla (routes/web/admin/admin.php,
| routes/web/configuracion/roles.php y routes/web/dashboard/dashboard.php).
| `admin` además exige un token de acceso total.
*/

/*
|--------------------------------------------------------------------------
| Usuarios (panel de usuarios)
|--------------------------------------------------------------------------
*/
Route::prefix('usuarios')->name('usuarios.')->whereNumber('user')->controller(UsuarioController::class)->group(function () {
    Route::get('/', 'index')->middleware('can:usuarios.ver')->name('index');
    Route::get('/{user}', 'show')->middleware('can:usuarios.ver')->name('show');
    Route::get('/{user}/conexiones', 'conexiones')->middleware('can:usuarios.ver')->name('conexiones');

    Route::patch('/{user}', 'update')->middleware('can:usuarios.editar')->name('update');
    Route::post('/{user}/documentos', 'documentos')->middleware('can:usuarios.editar')->name('documentos');
    Route::get('/{user}/permisos', 'permisos')->middleware('can:usuarios.editar')->name('permisos');
    Route::put('/{user}/permisos', 'actualizarPermisos')->middleware('can:usuarios.editar')->name('permisos.update');

    Route::post('/{user}/aprobar', 'aprobar')->middleware('can:usuarios.aprobar')->name('aprobar');
    Route::post('/{user}/banear', 'banear')->middleware('can:usuarios.aprobar')->name('banear');
    Route::post('/{user}/desbanear', 'desbanear')->middleware('can:usuarios.aprobar')->name('desbanear');

    // Dar o quitar el admin: acción crítica, solo admins con acceso total.
    Route::post('/{user}/admin', 'admin')->middleware('admin')->name('admin');
});

/*
|--------------------------------------------------------------------------
| Roles y catálogo de permisos (Configuración → Roles y permisos)
|--------------------------------------------------------------------------
| El catálogo es de solo lectura: los permisos viven en código.
*/
Route::middleware('can:roles.gestionar')->group(function () {
    Route::prefix('roles')->name('roles.')->whereNumber('role')->controller(RolController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{role}', 'show')->name('show');
        Route::put('/{role}', 'update')->name('update');
        Route::delete('/{role}', 'destroy')->name('destroy');
    });

    Route::get('/permisos', [PermisoController::class, 'index'])->name('permisos.index');
});

/*
|--------------------------------------------------------------------------
| Actividad
|--------------------------------------------------------------------------
*/
Route::get('/actividad', [ActividadController::class, 'index'])
    ->middleware('can:actividad.ver')->name('actividad.index');

/*
|--------------------------------------------------------------------------
| Agenda, materiales y reportes
|--------------------------------------------------------------------------
| Como en la web: agenda y pedir material los usa cualquiera con acceso;
| revisar material y registrar reportes es de administradores.
*/
Route::prefix('agenda')->name('agenda.')->whereNumber('event')->controller(AgendaController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/{event}', 'show')->name('show');
    Route::put('/{event}', 'update')->name('update');
    Route::delete('/{event}', 'destroy')->name('destroy');
});

Route::prefix('materiales')->name('materiales.')->whereNumber('materialRequest')->controller(MaterialController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/{materialRequest}', 'show')->name('show');
    Route::patch('/{materialRequest}/revision', 'revisar')->middleware('admin')->name('revisar');
});

Route::prefix('reportes')->name('reportes.')->controller(ReporteController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/resumen', 'resumen')->name('resumen');
    Route::post('/', 'store')->middleware('admin')->name('store');
});

/*
|--------------------------------------------------------------------------
| Vehículos, viáticos y viajes (solo administradores)
|--------------------------------------------------------------------------
*/
Route::middleware('admin')->group(function () {
    Route::prefix('vehiculos')->name('vehiculos.')->whereNumber('vehicle')->controller(VehiculoController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{vehicle}', 'show')->name('show');
    });

    Route::prefix('viaticos')->name('viaticos.')->whereNumber(['viatic', 'expense'])->controller(ViaticoController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{viatic}', 'show')->name('show');
        Route::patch('/{viatic}', 'update')->name('update');
        Route::delete('/{viatic}', 'destroy')->name('destroy');
        Route::post('/{viatic}/gastos', 'agregarGasto')->name('gastos.store');
        Route::patch('/{viatic}/gastos/{expense}', 'actualizarGasto')->name('gastos.update');
        Route::delete('/{viatic}/gastos/{expense}', 'eliminarGasto')->name('gastos.destroy');
    });

    Route::prefix('viajes')->name('viajes.')->whereNumber(['trip', 'expense'])->controller(ViajeController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{trip}', 'show')->name('show');
        Route::post('/{trip}/gastos', 'agregarGasto')->name('gastos.store');
        Route::patch('/{trip}/gastos/{expense}', 'actualizarGasto')->name('gastos.update');
        Route::delete('/{trip}/gastos/{expense}', 'eliminarGasto')->name('gastos.destroy');
        Route::post('/{trip}/finalizar', 'finalizar')->name('finalizar');
    });
});

/*
|--------------------------------------------------------------------------
| Mi cuenta: perfil, cuenta, notificaciones y tablero
|--------------------------------------------------------------------------
| Sin permiso especial: cada quien lo suyo, como en la web.
*/
Route::prefix('perfil')->name('perfil.')->controller(PerfilController::class)->group(function () {
    Route::get('/', 'show')->name('show');
    Route::patch('/', 'update')->name('update');
    Route::put('/contrasena', 'contrasena')->name('contrasena');
    Route::post('/firma', 'firma')->name('firma.store');
    Route::delete('/firma', 'quitarFirma')->name('firma.destroy');
});

Route::prefix('cuenta')->name('cuenta.')->controller(CuentaController::class)->group(function () {
    Route::get('/', 'show')->name('show');
    Route::post('/cerrar-sesiones-web', 'cerrarSesionesWeb')->name('sesionesWeb.destroy');
    Route::delete('/', 'destroy')->name('destroy');
});

Route::prefix('notificaciones')->name('notificaciones.')->controller(NotificacionController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/leer-todas', 'leerTodas')->name('leerTodas');
    Route::post('/{id}/leer', 'leer')->name('leer');
});

Route::prefix('tablero')->name('tablero.')->controller(TableroController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/catalogo', 'catalogo')->name('catalogo');
    Route::put('/tarjetas', 'guardar')->name('tarjetas.update');
    Route::delete('/tarjetas', 'restablecer')->name('tarjetas.destroy');
});
