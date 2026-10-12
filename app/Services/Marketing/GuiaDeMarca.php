<?php

namespace App\Services\Marketing;

use App\Models\BrandGuide;
use Illuminate\Http\Request;

/**
 * La guía de marca es un solo registro (colores y tipografías de
 * MediBuy). Si todavía no existe, nace con los valores por defecto la
 * primera vez que alguien la consulta.
 */
class GuiaDeMarca
{
    public function actual(): BrandGuide
    {
        return BrandGuide::first() ?? BrandGuide::create(BrandGuide::defaults());
    }

    /**
     * Reemplaza los colores y/o las tipografías. Lo que no se manda se
     * queda como estaba (se puede editar solo la paleta).
     */
    public function actualizar(Request $request): BrandGuide
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

        $brandGuide = $this->actual();

        $update = [];

        if (array_key_exists('colors', $data)) {
            $update['colors'] = $data['colors'];
        }

        if (array_key_exists('fonts', $data)) {
            $update['fonts'] = $data['fonts'];
        }

        $brandGuide->update($update);

        return $brandGuide;
    }
}
