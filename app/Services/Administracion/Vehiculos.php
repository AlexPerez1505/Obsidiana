<?php

namespace App\Services\Administracion;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Alta y búsqueda de vehículos de la empresa (con fotos y documentos).
 * Lo usan la pantalla de Vehículos y la API. Combustible y mantenimiento
 * son de Logística.
 */
class Vehiculos
{
    private const CARPETA_FOTOS = 'vehiculos/fotos';
    private const CARPETA_DOCS = 'vehiculos/documentos';

    public const DOCUMENTOS = [
        'circulation_card_doc',
        'verification_doc',
        'tenancy_doc',
        'insurance_doc',
    ];

    public const ESTADOS = [
        Vehicle::STATUS_ACTIVE => 'Activo',
        Vehicle::STATUS_MAINTENANCE => 'En mantenimiento',
        Vehicle::STATUS_INACTIVE => 'Inactivo',
    ];

    public static function reglasAlta(): array
    {
        return [
            'plate_number' => ['required', 'string', 'max:20', 'unique:vehicles,plate_number'],
            'vin' => ['nullable', 'string', 'max:50'],
            'brand' => ['nullable', 'string', 'max:50'],
            'model' => ['nullable', 'string', 'max:50'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'color' => ['nullable', 'string', 'max:50'],
            'insurance_policy_number' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:active,maintenance,inactive'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'circulation_card_doc' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'verification_doc' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'tenancy_doc' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'insurance_doc' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ];
    }

    /** Búsqueda por placa, marca, modelo o VIN, y filtro por estado. */
    public function consulta(?string $buscar, ?string $estado): Builder
    {
        $query = Vehicle::query();

        if ($buscar) {
            $query->where(function ($q) use ($buscar) {
                $q->where('plate_number', 'like', "%{$buscar}%")
                    ->orWhere('brand', 'like', "%{$buscar}%")
                    ->orWhere('model', 'like', "%{$buscar}%")
                    ->orWhere('vin', 'like', "%{$buscar}%");
            });
        }

        if ($estado) {
            $query->where('status', $estado);
        }

        return $query;
    }

    /** Registra el vehículo con las fotos y documentos que vengan en la petición. */
    public function registrar(array $data, Request $request): Vehicle
    {
        $data['photos'] = collect((array) $request->file('photos', []))
            ->map(fn ($foto) => $foto->store(self::CARPETA_FOTOS, 'public'))
            ->values()
            ->all();

        foreach (self::DOCUMENTOS as $campo) {
            if ($request->hasFile($campo)) {
                $data[$campo] = $request->file($campo)->store(self::CARPETA_DOCS, 'public');
            } else {
                unset($data[$campo]);
            }
        }

        return Vehicle::create($data);
    }
}
