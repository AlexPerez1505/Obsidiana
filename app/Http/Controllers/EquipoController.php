<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Equipo;
use App\Models\EquipmentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EquipoController extends Controller
{
    public function index(): View
    {
        $equipos = Equipo::latest()->get();

        return view('structure.gestion_Inventario.equipos.menu_productos', [
            'equipos' => $equipos,
        ]);
    }

    public function create(): View
    {
        return view('structure.gestion_Inventario.equipos.c_productos', [
            'equipmentTypes' => EquipmentType::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tipo_equipo' => ['required', 'string', 'max:255'],
            'subtipo' => ['nullable', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:255'],
            'modelo' => ['nullable', 'string', 'max:255'],
            'serie' => ['nullable', 'string', 'max:255'],
            'descripcion_equipo' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
            'evidencia_1' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'evidencia_2' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'evidencia_3' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'evidencia_video' => ['nullable', 'mimetypes:video/mp4,video/quicktime,video/x-m4v', 'max:10240'],
            'firma' => ['nullable', 'string'],
            'externo_interno' => ['nullable', 'in:Externo,Interno'],
        ]);

        $equipoData = [
            'tipo' => $data['tipo_equipo'],
            'subtipo' => $data['subtipo'] ?? null,
            'marca' => $data['marca'] ?? null,
            'modelo' => $data['modelo'] ?? null,
            'serie' => $data['serie'] ?? null,
            'descripcion' => $data['descripcion_equipo'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
            'externo_interno' => $data['externo_interno'] ?? null,
            'precio' => 0,
            'activo' => true,
        ];

        if ($request->hasFile('evidencia_1')) {
            $equipoData['imagen'] = $request->file('evidencia_1')->store('equipos', 'public');
        }

        foreach (['evidencia_2' => 'evidencia_2_path', 'evidencia_3' => 'evidencia_3_path', 'evidencia_video' => 'video_path'] as $field => $column) {
            if ($request->hasFile($field)) {
                $equipoData[$column] = $request->file($field)->store('evidencias/equipos', 'public');
            }
        }

        $equipoData['firma'] = $data['firma'] ?? null;

        Equipo::create($equipoData);

        return redirect()->route('inventory.equipos.index')
            ->with('status', 'Equipo registrado correctamente.');
    }
}
