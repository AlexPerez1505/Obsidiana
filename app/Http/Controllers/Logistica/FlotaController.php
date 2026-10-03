<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use App\Models\Ruta;
use App\Models\Vehicle;
use App\Models\VehiculoCombustible;
use App\Models\VehiculoMantenimiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Estado de la flota: kilometraje, combustible (con rendimiento km/l) y
 * mantenimiento con avisos de lo próximo a vencer.
 */
class FlotaController extends Controller
{
    /** Con cuántos días o km de anticipación se avisa un mantenimiento. */
    private const AVISO_DIAS = 15;
    private const AVISO_KM = 1000;

    public function index(): View
    {
        $inicioMes = now()->startOfMonth();

        $vehiculos = Vehicle::orderBy('plate_number')->get()->map(function (Vehicle $v) use ($inicioMes) {
            $ultimoMant = VehiculoMantenimiento::where('vehicle_id', $v->id)->latest('fecha')->latest('id')->first();

            return [
                'vehiculo' => $v,
                'odometro' => $this->odometro($v),
                'km_mes' => (float) Ruta::where('vehicle_id', $v->id)->where('iniciada_en', '>=', $inicioMes)->sum('km_recorridos'),
                'gasto_mes' => (float) VehiculoCombustible::where('vehicle_id', $v->id)->where('fecha', '>=', $inicioMes)->sum('monto')
                    + (float) VehiculoMantenimiento::where('vehicle_id', $v->id)->where('fecha', '>=', $inicioMes)->sum('costo'),
                'rendimiento' => $this->rendimiento($v),
                'ultimo_mant' => $ultimoMant,
                'alertas' => $this->alertas($v),
            ];
        });

        return view('structure.logistica.flota.index', [
            'filas' => $vehiculos,
            'conAlertas' => $vehiculos->filter(fn ($f) => count($f['alertas']))->count(),
        ]);
    }

    public function show(Vehicle $vehicle): View
    {
        return view('structure.logistica.flota.show', [
            'vehiculo' => $vehicle,
            'odometro' => $this->odometro($vehicle),
            'rendimiento' => $this->rendimiento($vehicle),
            'alertas' => $this->alertas($vehicle),
            'cargas' => VehiculoCombustible::with('usuario')->where('vehicle_id', $vehicle->id)->latest('fecha')->latest('id')->limit(40)->get(),
            'mantenimientos' => VehiculoMantenimiento::where('vehicle_id', $vehicle->id)->latest('fecha')->latest('id')->limit(40)->get(),
            'rutas' => Ruta::where('vehicle_id', $vehicle->id)->whereNotNull('km_recorridos')->latest('iniciada_en')->limit(10)->get(),
            'kmTotal' => (float) Ruta::where('vehicle_id', $vehicle->id)->sum('km_recorridos'),
        ]);
    }

    public function guardarCombustible(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $data = $request->validate([
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'litros' => ['required', 'numeric', 'min:0.1', 'max:500'],
            'monto' => ['required', 'numeric', 'min:0', 'max:100000'],
            'odometro_km' => ['nullable', 'integer', 'min:0', 'max:3000000'],
            'tanque_lleno' => ['nullable', 'boolean'],
            'estacion' => ['nullable', 'string', 'max:120'],
            'ticket' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
        ]);

        VehiculoCombustible::registrar($vehicle, $data, $request->file('ticket'), $request->user()->id);

        return back()->with('status', 'Carga de combustible registrada.');
    }

    public function eliminarCombustible(VehiculoCombustible $carga): RedirectResponse
    {
        $this->borrarArchivo($carga->ticket);
        $carga->delete();

        return back()->with('status', 'Carga eliminada.');
    }

    public function guardarMantenimiento(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $data = $request->validate([
            'tipo' => ['required', 'in:'.implode(',', array_keys(VehiculoMantenimiento::TIPOS))],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'odometro_km' => ['nullable', 'integer', 'min:0', 'max:3000000'],
            'costo' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'proveedor' => ['nullable', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'proximo_fecha' => ['nullable', 'date', 'after:fecha'],
            'proximo_km' => ['nullable', 'integer', 'min:0', 'max:3000000'],
            'comprobante' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
        ]);

        VehiculoMantenimiento::create([
            'vehicle_id' => $vehicle->id,
            'user_id' => $request->user()->id,
            'tipo' => $data['tipo'],
            'fecha' => $data['fecha'],
            'odometro_km' => $data['odometro_km'] ?? null,
            'costo' => $data['costo'] ?? 0,
            'proveedor' => $data['proveedor'] ?? null,
            'descripcion' => $data['descripcion'] ?? null,
            'proximo_fecha' => $data['proximo_fecha'] ?? null,
            'proximo_km' => $data['proximo_km'] ?? null,
            'comprobante' => $request->file('comprobante')?->store('logistica/mantenimiento', config('filesystems.fotos_disk', 'public')),
        ]);

        return back()->with('status', 'Mantenimiento registrado.');
    }

    public function eliminarMantenimiento(VehiculoMantenimiento $mantenimiento): RedirectResponse
    {
        $this->borrarArchivo($mantenimiento->comprobante);
        $mantenimiento->delete();

        return back()->with('status', 'Mantenimiento eliminado.');
    }

    // ------------------------------------------------------------------

    /**
     * Odómetro estimado: la última lectura capturada (combustible o
     * mantenimiento) más los km de las rutas hechas después de esa fecha.
     */
    public function odometro(Vehicle $v): ?int
    {
        $carga = VehiculoCombustible::where('vehicle_id', $v->id)->whereNotNull('odometro_km')->latest('fecha')->latest('id')->first();
        $mant = VehiculoMantenimiento::where('vehicle_id', $v->id)->whereNotNull('odometro_km')->latest('fecha')->latest('id')->first();

        $base = collect([$carga, $mant])->filter()->sortByDesc('fecha')->first();

        if (! $base) {
            return null;
        }

        $extra = (float) Ruta::where('vehicle_id', $v->id)->where('iniciada_en', '>', $base->fecha->copy()->endOfDay())->sum('km_recorridos');

        return (int) round($base->odometro_km + $extra);
    }

    /** km/l entre cargas de tanque lleno consecutivas con odómetro. */
    public function rendimiento(Vehicle $v): ?float
    {
        $cargas = VehiculoCombustible::where('vehicle_id', $v->id)
            ->where('tanque_lleno', true)->whereNotNull('odometro_km')
            ->orderBy('odometro_km')->get();

        if ($cargas->count() < 2) {
            return null;
        }

        $km = $cargas->last()->odometro_km - $cargas->first()->odometro_km;
        // Los litros de la primera carga llenaron el tanque antes del tramo medido
        $litros = $cargas->slice(1)->sum('litros');

        return $km > 0 && $litros > 0 ? round($km / $litros, 1) : null;
    }

    /** @return array<int, string> */
    public function alertas(Vehicle $v): array
    {
        $alertas = [];
        $odo = $this->odometro($v);

        // Lo último de cada tipo con fecha o km próximos
        $proximos = VehiculoMantenimiento::where('vehicle_id', $v->id)
            ->where(fn ($q) => $q->whereNotNull('proximo_fecha')->orWhereNotNull('proximo_km'))
            ->latest('fecha')->latest('id')->get()->unique('tipo');

        foreach ($proximos as $m) {
            if ($m->proximo_fecha) {
                $dias = (int) now()->startOfDay()->diffInDays($m->proximo_fecha, false);
                if ($dias < 0) {
                    $alertas[] = $m->tipoLabel().' vencido hace '.abs($dias).' día(s)';
                } elseif ($dias <= self::AVISO_DIAS) {
                    $alertas[] = $m->tipoLabel().' vence en '.$dias.' día(s)';
                }
            }
            if ($m->proximo_km && $odo !== null) {
                $faltan = $m->proximo_km - $odo;
                if ($faltan < 0) {
                    $alertas[] = $m->tipoLabel().' pasado por '.number_format(abs($faltan)).' km';
                } elseif ($faltan <= self::AVISO_KM) {
                    $alertas[] = $m->tipoLabel().' en '.number_format($faltan).' km';
                }
            }
        }

        if ($v->status === Vehicle::STATUS_MAINTENANCE) {
            $alertas[] = 'Marcado en mantenimiento';
        }

        return $alertas;
    }

    private function borrarArchivo(?string $path): void
    {
        if ($path) {
            Storage::disk(config('filesystems.fotos_disk', 'public'))->delete($path);
        }
    }
}
