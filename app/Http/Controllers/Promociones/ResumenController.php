<?php

namespace App\Http\Controllers\Promociones;

use App\Http\Controllers\Controller;
use App\Models\Campana;
use App\Models\MktEvento;
use App\Services\Promociones\Indicadores;
use App\Services\WhatsApp\EstadoWhatsApp;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResumenController extends Controller
{
    public function __invoke(Request $request, Indicadores $indicadores, EstadoWhatsApp $whatsapp): View
    {
        $periodo = Indicadores::periodoValido($request->get('periodo'));

        return view('structure.promociones.resumen', [
            'periodo' => $periodo,
            'k' => $indicadores->calcular($periodo),
            'top' => $indicadores->topCupones($periodo),
            'proximas' => Campana::where('estado', Campana::PROGRAMADA)->orderBy('programada_para')->limit(5)->get(),
            'enCurso' => Campana::whereIn('estado', [Campana::ENVIANDO, Campana::PAUSADA])->latest('iniciada_en')->limit(5)->get(),
            'actividad' => MktEvento::with(['usuario:id,name', 'campana:id,nombre'])->latest('id')->limit(8)->get(),
            'whatsapp' => $whatsapp,
        ]);
    }
}
