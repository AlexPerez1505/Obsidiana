<?php

namespace App\Http\Controllers\Mantenimiento;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Tablero de inicio para el rol Mantenimiento.
 *
 * Vacío por ahora: se llena cuando se defina en qué consiste el rol.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        return view('mantenimiento.dashboard');
    }
}
