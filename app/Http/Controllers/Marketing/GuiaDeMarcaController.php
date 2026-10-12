<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;

use App\Models\BrandGuide;
use App\Services\Marketing\GuiaDeMarca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuiaDeMarcaController extends Controller
{
    public function __construct(private GuiaDeMarca $guia)
    {
    }

    public function index(): View
    {
        return view('structure.gestion_marketing.guia_marca.guia_marca', [
            'brandGuide' => $this->guia->actual(),
        ]);
    }

    public function create(): View
    {
        // Sin guardar: el formulario puede abrirse y cerrarse sin crear nada.
        $brandGuide = BrandGuide::first() ?? BrandGuide::make(BrandGuide::defaults());

        return view('structure.gestion_marketing.guia_marca.crear_guia_marca', [
            'brandGuide' => $brandGuide,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->guia->actualizar($request);

        return redirect()->route('marketing.guia_de_marca.index')->with('status', 'Guía de marca actualizada.');
    }
}
