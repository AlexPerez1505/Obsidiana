<?php

namespace App\Services\Cotizaciones;

use App\Models\Customer;
use App\Models\EquipmentType;
use App\Models\FichaTecnica;
use App\Models\Paquete;
use App\Models\Producto;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Los buscadores con los que se arma una cotización o una venta: clientes,
 * productos y paquetes, y fichas técnicas.
 *
 * Aquí viven las consultas (qué se busca, en qué campos y cuántos
 * resultados); la web y la API solo cambian la forma de presentarlos.
 */
class BuscadorCotizador
{
    /** @return Collection<int, Customer> */
    public function clientes(User $usuario, string $q): Collection
    {
        // Mismo criterio que el directorio: quien no tiene permiso de ver
        // todos los clientes solo encuentra los que él registró.
        return Customer::visiblesPara($usuario)
            // Se traen de una vez: sin esto, calcular el saldo de cada
            // cliente dispararía una consulta por venta.
            ->with(['congress', 'category', 'asesor', 'ventas.cobros', 'ventas.pagos'])
            ->withCount('cotizaciones')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nombre', 'like', "%{$q}%")
                        ->orWhere('apellido', 'like', "%{$q}%")
                        ->orWhere('gmail', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%")
                        ->orWhere('rfc', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre')
            ->limit(15)
            ->get();
    }

    /**
     * Historial: para saber con quién se está tratando antes de armarle
     * otra venta. Espera al cliente como lo regresa clientes().
     *
     * @return array{compras: int, cotizaciones: int, saldo: float, ultima_compra: ?CarbonInterface}
     */
    public function historial(Customer $cliente): array
    {
        $ventas = $cliente->ventas->where('estado', '!=', 'cancelada');

        return [
            'compras' => $ventas->count(),
            'cotizaciones' => (int) $cliente->cotizaciones_count,
            'saldo' => round($cliente->saldoPendiente(), 2),
            'ultima_compra' => $ventas->max('created_at'),
        ];
    }

    /** @return Collection<int, Producto> */
    public function productos(string $q, array $filtros = []): Collection
    {
        return Producto::query()
            ->with([
                'fichaTecnica',
                // El congreso viene con la pieza: el selector avisa cuáles
                // no están en el almacén, y sin esto era una consulta por pieza.
                'serialesDisponibles' => fn ($s) => $s->with('congress')->orderBy('id')->limit(60),
            ])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('tipo_equipo', 'like', "%{$q}%")
                        ->orWhere('subtipo', 'like', "%{$q}%")
                        ->orWhere('modelo', 'like', "%{$q}%")
                        ->orWhere('marca', 'like', "%{$q}%")
                        ->orWhere('no_serie', 'like', "%{$q}%");
                });
            })
            ->when($filtros, fn ($query) => $this->filtrarProductos($query, $filtros))
            ->orderBy('tipo_equipo')
            ->limit(20)
            ->get();
    }

    /** @return Collection<int, Paquete> */
    public function paquetes(string $q, array $filtros = []): Collection
    {
        return Paquete::where('activo', true)
            ->with(['productos', 'equipos'])
            ->when($filtros, fn ($query) => $query->whereHas('productos', fn ($productos) => $this->filtrarProductos($productos, $filtros)))
            ->when($q !== '', fn ($query) => $query->where('nombre', 'like', "%{$q}%"))
            ->orderBy('nombre')
            ->limit(10)
            ->get();
    }

    public function filtrosProductos(): Collection
    {
        return EquipmentType::with(['subtypes.brands', 'subtypes.equipmentModels'])->orderBy('name')->get()->map(fn ($tipo) => [
            'id' => $tipo->id,
            'nombre' => $tipo->name,
            'subtipos' => $tipo->subtypes->sortBy('name')->map(fn ($subtipo) => [
                'id' => $subtipo->id,
                'nombre' => $subtipo->name,
                'marcas' => $subtipo->brands->sortBy('name')->map(fn ($marca) => [
                    'id' => $marca->id, 'nombre' => $marca->name,
                    // El modelo depende de la marca dentro de este subtipo.
                    'modelos' => $subtipo->equipmentModels->where('brand_id', $marca->id)->sortBy('name')
                        ->map(fn ($modelo) => ['id' => $modelo->id, 'nombre' => $modelo->name])->values(),
                ])->values(),
            ])->values(),
        ])->values();
    }

    private function filtrarProductos(\Illuminate\Database\Eloquent\Builder $query, array $filtros): \Illuminate\Database\Eloquent\Builder
    {
        foreach (['equipment_type_id', 'subtype_id', 'brand_id', 'equipment_model_id'] as $campo) {
            if (! empty($filtros[$campo])) {
                $query->where('productos.'.$campo, $filtros[$campo]);
            }
        }

        return $query;
    }

    /** Las marcas de los productos del paquete, para mostrarlas como una sola. */
    public function marcasDe(Paquete $paquete): string
    {
        return $paquete->productos->pluck('marca')->filter()->unique()->implode(', ');
    }

    /** @return Collection<int, FichaTecnica> */
    public function fichas(string $q): Collection
    {
        return FichaTecnica::where('activo', true)
            ->when($q !== '', fn ($query) => $query->where('titulo', 'like', "%{$q}%"))
            ->orderBy('titulo')
            ->limit(20)
            ->get();
    }
}
