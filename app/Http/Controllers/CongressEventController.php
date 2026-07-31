<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CongressEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CongressEventController extends Controller
{
    /**
     * Muestra el formulario para crear un congreso.
     */
    public function create(): View
    {
        return view('structure.Configuracion.Catalogos.Congresos.c_congresos', [
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Guarda un nuevo congreso.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:2048'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'assembly_time' => ['required', 'date_format:H:i'],
            'disassembly_time' => ['required', 'date_format:H:i'],
            'download_access' => ['required', 'boolean'],
            'download_text' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('congresses', 'public');
        }

        CongressEvent::create($data);

        return redirect()->back()->with('status', 'Congreso guardado correctamente.');
    }
}
