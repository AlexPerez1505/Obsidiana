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
        $validated = $request->validate([
            'clear_colors' => ['nullable', 'boolean'],
            'clear_fonts' => ['nullable', 'boolean'],
            'colors' => ['nullable', 'array', 'required_without:clear_colors'],
            'colors.*.name' => ['nullable', 'string', 'max:100'],
            'colors.*.hex' => ['nullable', 'string', 'max:7'],
            'fonts' => ['nullable', 'array'],
            'fonts.*.name' => ['nullable', 'string', 'max:100'],
            'fonts.*.sample' => ['nullable', 'string', 'max:255'],
            'fonts.*.usage' => ['nullable', 'string', 'max:100'],
            'fonts.*.description' => ['nullable', 'string', 'max:500'],
        ]);

        if (! empty($validated['clear_colors'])) {
            $validated['colors'] = [];
        } elseif (! array_key_exists('colors', $validated)) {
            unset($validated['colors']);
        }

        if (! empty($validated['clear_fonts'])) {
            $validated['fonts'] = [];
        } elseif (! array_key_exists('fonts', $validated)) {
            unset($validated['fonts']);
        }

        unset($validated['clear_colors'], $validated['clear_fonts']);

        $brandGuide = BrandGuide::first();

        if (! $brandGuide) {
            $brandGuide = new BrandGuide();
            $brandGuide->fill($validated)->save();
        } else {
            $brandGuide->update($validated);
        }

        return redirect()->route('marketing.guia.index')->with('status', 'Paleta y tipografía actualizadas.');
    }
}
