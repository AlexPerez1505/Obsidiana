<?php

namespace App\Http\Controllers\Mantenimiento;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Configuración del rol Mantenimiento.
 *
 * Vacío por ahora: se llena cuando se defina qué se puede configurar aquí.
 */
class ConfiguracionController extends Controller
{
    public function index(): View
    {
        return view('mantenimiento.configuracion');
    }
}
