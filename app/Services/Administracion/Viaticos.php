<?php

namespace App\Services\Administracion;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\Viatic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Viáticos: alta (con sus gastos iniciales y fotos del ticket), edición,
 * baja y quién ve cuáles. Lo usan la pantalla de Viáticos y la API.
 */
class Viaticos
{
    private const CARPETA_TICKETS = 'viaticos/tickets';

    /** Campo de total del formulario => tipo de gasto que genera. */
    private const TOTALES = [
        'toll' => 'tolls',
        'fuel' => 'fuel',
        'meal' => 'meals',
        'lodging' => 'lodging',
        'other' => 'additional',
    ];

    public const ESTADOS = [
        Viatic::STATUS_PENDING => 'Pendiente',
        Viatic::STATUS_APPROVED => 'Aprobado',
        Viatic::STATUS_REJECTED => 'Rechazado',
    ];

    public const REGLAS = [
        'vehicle_id' => ['nullable', 'exists:vehicles,id'],
        'vehicle_name' => ['nullable', 'string', 'max:100'],
        'place' => ['nullable', 'string', 'max:255'],
        'tolls' => ['nullable', 'numeric', 'min:0'],
        'fuel' => ['nullable', 'numeric', 'min:0'],
        'meals' => ['nullable', 'numeric', 'min:0'],
        'lodging' => ['nullable', 'numeric', 'min:0'],
        'additional' => ['nullable', 'numeric', 'min:0'],
        'description' => ['nullable', 'string', 'max:1000'],
        'expense_date' => ['nullable', 'date'],
        'ticket_photos' => ['nullable', 'array'],
        'ticket_photos.*' => ['image', 'max:5120'],
    ];

    /** Al editar se pueden quitar fotos ya guardadas (por su ruta). */
    public const REGLAS_QUITAR_FOTOS = [
        'quitar_fotos' => ['nullable', 'array'],
        'quitar_fotos.*' => ['string'],
    ];

    /** El administrador ve todos; los demás, solo los suyos. */
    public function visiblesPara(User $user, ?string $buscar = null, ?string $estado = null): Builder
    {
        $query = Viatic::with(['user', 'vehicle'])->latest();

        if (! Administrador::pleno($user)) {
            $query->where('user_id', $user->id);
        }

        if ($buscar) {
            $query->where(function ($q) use ($buscar) {
                $q->where('place', 'like', "%{$buscar}%")
                    ->orWhere('vehicle_name', 'like', "%{$buscar}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$buscar}%"));
            });
        }

        if ($estado) {
            $query->where('status', $estado);
        }

        return $query;
    }

    public function puedeVer(Viatic $viatic, ?User $user): bool
    {
        return $user && (Administrador::pleno($user) || (int) $viatic->user_id === (int) $user->id);
    }

    /**
     * Registra el viático a nombre de $quien. Cada total que llegue con
     * monto se vuelve además un gasto suelto, que es lo que se edita
     * después en el detalle.
     *
     * @param  array<int, UploadedFile>  $fotos
     */
    public function registrar(array $data, User $quien, array $fotos = []): Viatic
    {
        $data['user_id'] = $quien->id;
        $data = $this->conNombreDeVehiculo($data);

        $data['ticket_photos'] = collect($fotos)
            ->map(fn (UploadedFile $archivo) => $archivo->store(self::CARPETA_TICKETS, 'public'))
            ->all();

        $viatic = Viatic::create($data);

        foreach (self::TOTALES as $tipo => $campo) {
            $monto = (float) ($data[$campo] ?? 0);

            if ($monto > 0) {
                $viatic->expenses()->create([
                    'type' => $tipo,
                    'label' => GastosDeViaje::TIPOS[$tipo],
                    'amount' => $monto,
                    'icon' => GastosDeViaje::ICONOS[$tipo],
                ]);
            }
        }

        return $viatic;
    }

    /**
     * Guarda los cambios. Las fotos que quedan son las que había menos las
     * marcadas para quitar, más las nuevas.
     *
     * Solo se borran del disco las que de verdad eran de este viático: una
     * ruta ajena en quitar_fotos se ignora.
     *
     * @param  array<int, UploadedFile>  $fotosNuevas
     */
    public function actualizar(Viatic $viatic, array $data, array $fotosNuevas = []): Viatic
    {
        $data = $this->conNombreDeVehiculo($data);

        $actuales = collect($viatic->ticket_photos ?? []);
        $quitar = $actuales->intersect($data['quitar_fotos'] ?? [])->values();

        $this->borrarFotos($quitar->all());

        $nuevas = collect($fotosNuevas)
            ->map(fn (UploadedFile $archivo) => $archivo->store(self::CARPETA_TICKETS, 'public'));

        $data['ticket_photos'] = $actuales->diff($quitar)->concat($nuevas)->values()->all();
        unset($data['quitar_fotos']);

        $viatic->update($data);

        return $viatic;
    }

    public function eliminar(Viatic $viatic): void
    {
        $this->borrarFotos($viatic->ticket_photos ?? []);

        $viatic->delete();
    }

    /** Con vehículo del catálogo, el nombre se toma de ahí. */
    private function conNombreDeVehiculo(array $data): array
    {
        if (isset($data['vehicle_id']) && $data['vehicle_id']) {
            $vehicle = Vehicle::find($data['vehicle_id']);
            $data['vehicle_name'] = $vehicle ? "{$vehicle->brand} {$vehicle->model}" : null;
        }

        return $data;
    }

    private function borrarFotos(array $rutas): void
    {
        foreach ($rutas as $ruta) {
            Storage::disk('public')->delete($ruta);
        }
    }
}
