<?php

namespace App\Http\Controllers\Api\V1\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Marketing\GuiaDeMarcaResource;
use App\Services\Marketing\GuiaDeMarca;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Marketing
 */
class GuiaDeMarcaController extends Controller
{
    public function __construct(private GuiaDeMarca $guia)
    {
    }

    /**
     * Guía de marca.
     *
     * Paleta de colores y tipografías de la marca. Si nunca se ha
     * editado, trae los valores por defecto.
     */
    public function show(): JsonResponse
    {
        return $this->respuesta(new GuiaDeMarcaResource($this->guia->actual()));
    }

    /**
     * Editar la guía de marca.
     *
     * `colors` (lista de `{name, hex}`) y/o `fonts` (lista de `{name,
     * sample, usage, description}`). Cada lista que mandes reemplaza
     * completa a la anterior; la que no mandes se queda igual.
     */
    #[BodyParameter('colors', 'Paleta: lista de {name, hex}.', type: 'array', example: [['name' => 'Azul MediBuy', 'hex' => '#1B5B9E']])]
    #[BodyParameter('fonts', 'Tipografías: lista de {name, sample, usage, description}.', type: 'array')]
    public function update(Request $request): JsonResponse
    {
        return $this->respuesta(
            (new GuiaDeMarcaResource($this->guia->actualizar($request)))->additional(['message' => 'Guía de marca actualizada.'])
        );
    }

    /**
     * La guía nace sola en la primera consulta y Laravel contestaría 201
     * por ser un registro recién creado; para quien la pide es una
     * lectura (o una edición) normal.
     */
    private function respuesta(GuiaDeMarcaResource $resource): JsonResponse
    {
        return $resource->response()->setStatusCode(200);
    }
}
