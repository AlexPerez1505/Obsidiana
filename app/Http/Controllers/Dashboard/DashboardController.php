<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\DashboardWidgets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tablero de inicio.
 *
 * Cada quien elige que tarjetas ve, en que orden y de que tamaño. La
 * preferencia vive en users.dashboard_widgets.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $activas = DashboardWidgets::paraUsuario($user);

        // Solo se calculan los datos de lo que realmente se va a pintar.
        $datos = [];

        foreach ($activas as $widget) {
            // El tamaño va incluido: una tarjeta grande trae además su
            // desglose, una chica se queda con el dato principal.
            $datos[$widget['id']] = DashboardWidgets::datos(
                $widget['id'], $user, $widget['w'], $widget['h']
            );
        }

        return view('dashboard.index', [
            'activas' => $activas,
            'datos' => $datos,
            'catalogo' => DashboardWidgets::catalogo(),
            // Los límites de cada tarjeta viajan a la vista para que el
            // arrastre no la deje más chica ni más grande de lo razonable.
            'limites' => collect(DashboardWidgets::catalogo())
                ->map(fn ($def, $id) => DashboardWidgets::definicion($id))
                ->all(),
        ]);
    }

    /** Guarda el acomodo elegido en el panel de personalizar. */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'widgets' => ['nullable', 'array'],
            'widgets.*.id' => ['required', 'string'],
            'widgets.*.w' => ['required', 'integer'],
            'widgets.*.h' => ['required', 'integer'],
        ]);

        $limpio = DashboardWidgets::normalizar($request->input('widgets', []));

        $request->user()->update(['dashboard_widgets' => $limpio]);

        return redirect()->route('dashboard')->with('status', 'Tablero actualizado.');
    }

    /** Vuelve al acomodo de fábrica. */
    public function reset(Request $request): RedirectResponse
    {
        $request->user()->update(['dashboard_widgets' => null]);

        return redirect()->route('dashboard')->with('status', 'Tablero restaurado al acomodo original.');
    }
}
