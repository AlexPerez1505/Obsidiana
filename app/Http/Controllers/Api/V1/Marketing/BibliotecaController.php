<?php

namespace App\Http\Controllers\Api\V1\Marketing;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Marketing\TareaResource;
use App\Models\Task;
use App\Services\Marketing\TareasDeMarketing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Marketing
 */
class BibliotecaController extends Controller
{
    use ListadoApi;

    /**
     * Biblioteca de flyers.
     *
     * Las piezas aprobadas que tienen imagen, de la más reciente a la más
     * vieja. `category` filtra por categoría.
     */
    public function index(Request $request, TareasDeMarketing $tareas): AnonymousResourceCollection
    {
        $flyers = $tareas->flyersDeBiblioteca()
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->query('category')))
            ->orderByDesc('id')
            ->paginate($this->porPagina($request));

        return TareaResource::collection($flyers);
    }

    /**
     * Descargar un flyer.
     *
     * El mismo archivo que baja la web: la imagen de la pieza o, si no
     * tiene, lo que haya en el enlace de entrega. Si el enlace es una
     * página (Canva, Drive), responde 302 hacia él. 404 si la pieza no
     * está aprobada o no tiene nada que bajar.
     */
    public function descargar(Task $task, TareasDeMarketing $tareas): Response
    {
        return $tareas->descargarFlyer($task);
    }
}
