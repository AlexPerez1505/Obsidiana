<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use App\Models\Ruta;
use App\Models\Vehicle;
use App\Models\VehiculoCombustible;
use App\Models\VehiculoMantenimiento;
use App\Services\Logistica\ControlDeFlota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Estado de la flota: kilometraje, combustible (con rendimiento km/l) y
 * mantenimiento con avisos de lo próximo a vencer. Los cálculos viven en
 * ControlDeFlota (los comparte la API).
 */
class FlotaController extends Controller
{
    public function __construct(private ControlDeFlota $flota) {}

    public function index(): View
    {
        $inicioMes = now()->startOfMonth();

        $vehiculos = Vehicle::orderBy('plate_number')->get()->map(fn (Vehicle $v) => $this->flota->resumen($v, $inicioMes));

        return view('structure.logistica.flota.index', [
            'filas' => $vehiculos,
            'conAlertas' => $vehiculos->filter(fn ($f) => count($f['alertas']))->count(),
        ]);
    }

    public function show(Vehicle $vehicle): View
    {
        return view('structure.logistica.flota.show', [
            'vehiculo' => $vehicle,
            'odometro' => $this->flota->odometro($vehicle),
            'rendimiento' => $this->flota->rendimiento($vehicle),
            'alertas' => $this->flota->alertas($vehicle),
            'cargas' => VehiculoCombustible::with('usuario')->where('vehicle_id', $vehicle->id)->latest('fecha')->latest('id')->limit(40)->get(),
            'mantenimientos' => VehiculoMantenimiento::where('vehicle_id', $vehicle->id)->latest('fecha')->latest('id')->limit(40)->get(),
            'rutas' => Ruta::where('vehicle_id', $vehicle->id)->whereNotNull('km_recorridos')->latest('iniciada_en')->limit(10)->get(),
            'kmTotal' => $this->flota->kmTotal($vehicle),
        ]);
    }

    public function guardarCombustible(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $data = $request->validate($this->flota->reglasCombustible());

        VehiculoCombustible::registrar($vehicle, $data, $request->file('ticket'), $request->user()->id);

        return back()->with('status', 'Carga de combustible registrada.');
    }

    public function eliminarCombustible(VehiculoCombustible $carga): RedirectResponse
    {
        $this->flota->eliminarCarga($carga);

        return back()->with('status', 'Carga eliminada.');
    }

    public function guardarMantenimiento(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $data = $request->validate($this->flota->reglasMantenimiento());

        $this->flota->registrarMantenimiento($vehicle, $data, $request->file('comprobante'), $request->user()->id);

        return back()->with('status', 'Mantenimiento registrado.');
    }

    public function eliminarMantenimiento(VehiculoMantenimiento $mantenimiento): RedirectResponse
    {
        $this->flota->eliminarMantenimiento($mantenimiento);

        return back()->with('status', 'Mantenimiento eliminado.');
    }
}
