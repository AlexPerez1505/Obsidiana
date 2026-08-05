<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;

use App\Models\BrandGuide;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuiaDeMarcaController extends Controller
{
    public function index(): View
    {
        $brandGuide = BrandGuide::first();

        if (! $brandGuide) {
            $brandGuide = BrandGuide::create(BrandGuide::defaults());
        }

        return view('structure.gestion_marketing.guia_marca.guia_marca', [
            'brandGuide' => $brandGuide,
        ]);
    }

    public function create(): View
    {
        $brandGuide = BrandGuide::first();

        if (! $brandGuide) {
            $brandGuide = BrandGuide::make(BrandGuide::defaults());
        }

        return view('structure.gestion_marketing.guia_marca.crear_guia_marca', [
            'brandGuide' => $brandGuide,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'colors' => ['nullable', 'array'],
            'colors.*.name' => ['required', 'string', 'max:100'],
            'colors.*.hex' => ['required', 'string', 'max:7'],
            'fonts' => ['nullable', 'array'],
            'fonts.*.name' => ['required', 'string', 'max:100'],
            'fonts.*.sample' => ['required', 'string', 'max:255'],
            'fonts.*.usage' => ['required', 'string', 'max:100'],
            'fonts.*.description' => ['required', 'string', 'max:500'],
        ]);

        $brandGuide = BrandGuide::first();

        if (! $brandGuide) {
            $brandGuide = BrandGuide::create(BrandGuide::defaults());
        }

        $update = [];

        if (array_key_exists('colors', $data)) {
            $update['colors'] = $data['colors'];
        }

        if (array_key_exists('fonts', $data)) {
            $update['fonts'] = $data['fonts'];
        }

        $brandGuide->update($update);

        return redirect()->route('marketing.guia_de_marca.index')->with('status', 'Guía de marca actualizada.');
    }
}
