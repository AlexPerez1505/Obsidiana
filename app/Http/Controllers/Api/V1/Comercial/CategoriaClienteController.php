<?php

namespace App\Http\Controllers\Api\V1\Comercial;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Comercial\CategoriaClienteResource;
use App\Services\Clientes\RegistroClientes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Clientes
 */
class CategoriaClienteController extends Controller
{
    /**
     * Crear categoría de cliente.
     *
     * La misma alta rápida del formulario web ("+ nueva categoría"). El
     * nombre no se puede repetir. Pide `clientes.crear`. La lista completa
     * viene en `GET /clientes/catalogos`.
     */
    public function store(Request $request, RegistroClientes $registro): JsonResponse
    {
        $data = $request->validate($registro->reglasCategoria());

        return (new CategoriaClienteResource($registro->registrarCategoria($data)))
            ->additional(['message' => 'Categoría creada.'])
            ->response()
            ->setStatusCode(201);
    }
}
