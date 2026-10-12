<?php

namespace App\Http\Controllers\Api\V1\Rh;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Rh\EmpleadoResource;
use App\Models\EmployeeBiometric;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Huellas registradas por empleado. Solo se ve qué dedos y cuándo: la
 * plantilla biométrica nunca sale por la API.
 *
 * @tags RH · Asistencia
 */
class HuellaController extends Controller
{
    use ListadoApi;

    /**
     * Listar empleados con sus huellas.
     *
     * Las cuentas aprobadas con los dedos que tienen registrados.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            // Busca en nombre, correo o número de checador.
            'buscar' => ['nullable', 'string', 'max:100'],
            // true: solo quien no tiene ninguna huella.
            'sin_huella' => ['nullable', 'boolean'],
        ]);

        $q = User::with('huellas')->withCount('huellas')->where('status', User::STATUS_APPROVED)
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $b = '%'.$request->query('buscar').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $b)->orWhere('email', 'like', $b)->orWhere('checador_id', 'like', $b));
            })
            ->when($request->boolean('sin_huella'), fn ($q) => $q->whereDoesntHave('huellas'));

        $this->ordenar($q, $request, ['name', 'id'], 'name');

        return EmpleadoResource::collection($q->paginate($this->porPagina($request)));
    }

    /**
     * Eliminar una huella.
     *
     * El lector deja de reconocer ese dedo en cuanto vuelve a descargar las
     * plantillas.
     */
    public function destroy(EmployeeBiometric $huella): JsonResponse
    {
        $nombre = $huella->user->name;
        $huella->delete();

        return response()->json(['message' => "Huella eliminada de {$nombre}."]);
    }
}
