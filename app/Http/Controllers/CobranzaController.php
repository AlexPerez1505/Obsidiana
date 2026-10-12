<?php

namespace App\Http\Controllers;

use App\Services\Ventas\PanoramaCobranza;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Panorama de cobranza y ventas: cuánto me deben, quién está atrasado,
 * cuánto vendí este mes y qué asesor vende más. Los números los arma
 * PanoramaCobranza (los usa también la API).
 */
class CobranzaController extends Controller
{
    public function __construct(private readonly PanoramaCobranza $panorama)
    {
    }

    public function index(Request $request): View
    {
        $estado = $request->get('estado', 'deben');
        $asesorId = $request->integer('asesor') ?: null;
        $buscar = trim((string) $request->get('buscar', ''));

        $filas = $this->panorama->filas($request->user(), $asesorId);

        return view('structure.commercial_management.cobranza.index', [
            'filas' => $this->panorama->filtrar($filas, $estado, $buscar),
            'resumen' => $this->panorama->resumen($filas, $request->user()),
            'porMes' => $this->panorama->porMes($request->user()),
            'asesores' => $this->panorama->rankingAsesores($request->user()),
            'listaAsesores' => $this->panorama->listaAsesores($request->user()),
            'estado' => $estado,
            'asesorId' => $asesorId,
            'buscar' => $buscar,
            'conteos' => $this->panorama->conteos($filas),
        ]);
    }
}
