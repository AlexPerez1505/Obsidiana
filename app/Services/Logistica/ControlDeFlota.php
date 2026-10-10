<?php

namespace App\Services\Logistica;

use App\Models\Ruta;
use App\Models\Vehicle;
use App\Models\VehiculoCombustible;
use App\Models\VehiculoMantenimiento;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Estado de cada vehículo de la flota: odómetro estimado, rendimiento
 * km/l, gasto del mes y avisos de mantenimiento. Lo usan la pantalla de
 * Flota y la API, para que los números salgan iguales en las dos.
 */
class ControlDeFlota
{
    /** Con cuántos días o km de anticipación se avisa un mantenimiento. */
    public const AVISO_DIAS = 15;
    public const AVISO_KM = 1000;

    /** Reglas de una carga de combustible capturada en la oficina. */
    public function reglasCombustible(): array
    {
        return [
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'litros' => ['required', 'numeric', 'min:0.1', 'max:500'],
            'monto' => ['required', 'numeric', 'min:0', 'max:100000'],
            'odometro_km' => ['nullable', 'integer', 'min:0', 'max:3000000'],
            'tanque_lleno' => ['nullable', 'boolean'],
            'estacion' => ['nullable', 'string', 'max:120'],
            'ticket' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
        ];
    }

    public function reglasMantenimiento(): array
    {
        return [
            'tipo' => ['required', 'in:'.implode(',', array_keys(VehiculoMantenimiento::TIPOS))],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'odometro_km' => ['nullable', 'integer', 'min:0', 'max:3000000'],
            'costo' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'proveedor' => ['nullable', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'proximo_fecha' => ['nullable', 'date', 'after:fecha'],
            'proximo_km' => ['nullable', 'integer', 'min:0', 'max:3000000'],
            'comprobante' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
        ];
    }

    /**
     * Renglón del tablero de flota.
     *
     * @return array{vehiculo: Vehicle, odometro: ?int, km_mes: float, gasto_mes: float, rendimiento: ?float, ultimo_mant: ?VehiculoMantenimiento, alertas: array<int, string>}
     */
    public function resumen(Vehicle $v, ?Carbon $inicioMes = null): array
    {
        $inicioMes ??= now()->startOfMonth();
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
    }

    /** Kilómetros recorridos en todas las rutas del vehículo. */
    public function kmTotal(Vehicle $v): float
    {
        return (float) Ruta::where('vehicle_id', $v->id)->sum('km_recorridos');
    }

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

    public function registrarMantenimiento(Vehicle $vehicle, array $data, ?UploadedFile $comprobante, int $userId): VehiculoMantenimiento
    {
        return VehiculoMantenimiento::create([
            'vehicle_id' => $vehicle->id,
            'user_id' => $userId,
            'tipo' => $data['tipo'],
            'fecha' => $data['fecha'],
            'odometro_km' => $data['odometro_km'] ?? null,
            'costo' => $data['costo'] ?? 0,
            'proveedor' => $data['proveedor'] ?? null,
            'descripcion' => $data['descripcion'] ?? null,
            'proximo_fecha' => $data['proximo_fecha'] ?? null,
            'proximo_km' => $data['proximo_km'] ?? null,
            'comprobante' => $comprobante?->store('logistica/mantenimiento', config('filesystems.fotos_disk', 'public')),
        ]);
    }

    /** Borra la carga junto con la foto de su ticket. */
    public function eliminarCarga(VehiculoCombustible $carga): void
    {
        $this->borrarArchivo($carga->ticket);
        $carga->delete();
    }

    /** Borra el mantenimiento junto con su comprobante. */
    public function eliminarMantenimiento(VehiculoMantenimiento $mantenimiento): void
    {
        $this->borrarArchivo($mantenimiento->comprobante);
        $mantenimiento->delete();
    }

    private function borrarArchivo(?string $path): void
    {
        if ($path) {
            Storage::disk(config('filesystems.fotos_disk', 'public'))->delete($path);
        }
    }
}
