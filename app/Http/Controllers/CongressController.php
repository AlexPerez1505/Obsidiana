<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Congress;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CongressController extends Controller
{
    /**
     * Muestra el formulario para crear un congreso.
     */
    public function create(): View
    {
        return view('structure.Configuracion.Catalogos.Congresos.c_congresos', [
            'categories' => Category::query()->orderBy('nombre')->get(),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    /**
     * Guarda un nuevo congreso.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'path_archivo' => ['nullable', 'string', 'max:255'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_finalizacion' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'hora_montaje' => ['required', 'date_format:H:i'],
            'hora_desmontaje' => ['required', 'date_format:H:i'],
            'descarga_acceso' => ['required', 'boolean'],
            'descarga_texto' => ['nullable', 'string'],
            'acceso_subir' => ['required', 'boolean'],
            'subir_texto' => ['nullable', 'string'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'comments' => ['nullable', 'string', 'max:5000'],
        ]);

        $congress = Congress::create($data);

        return redirect()->route('configuracion.catalogos.index')->with('status_congress', 'Congreso guardado correctamente.');
    }

    /**
     * Muestra el formulario para editar un congreso.
     */
    public function edit(Congress $congress): View
    {
        return view('structure.Configuracion.Catalogos.Congresos.u_congresos', [
            'congress' => $congress,
            'categories' => Category::query()->orderBy('nombre')->get(),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    /**
     * Actualiza un congreso existente.
     */
    public function update(Request $request, Congress $congress): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'path_archivo' => ['nullable', 'string', 'max:255'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_finalizacion' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'hora_montaje' => ['required', 'date_format:H:i'],
            'hora_desmontaje' => ['required', 'date_format:H:i'],
            'descarga_acceso' => ['required', 'boolean'],
            'descarga_texto' => ['nullable', 'string'],
            'acceso_subir' => ['required', 'boolean'],
            'subir_texto' => ['nullable', 'string'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'comments' => ['nullable', 'string', 'max:5000'],
        ]);

        $congress->update($data);

        return redirect()->route('configuracion.catalogos.index')->with('status_congress', 'Congreso actualizado correctamente.');
    }

    /**
     * Muestra el detalle de un congreso.
     */
    public function show(Congress $congress): View
    {
        return view('structure.Configuracion.Catalogos.Congresos.r_congresos', [
            'congress' => $congress,
        ]);
    }

    /**
     * Muestra la confirmación para eliminar un congreso.
     */
    public function delete(Congress $congress): View
    {
        return view('structure.Configuracion.Catalogos.Congresos.d_congresos', [
            'congress' => $congress,
        ]);
    }

    /**
     * Elimina un congreso existente.
     */
    public function destroy(Congress $congress): RedirectResponse
    {
        $congress->delete();

        return redirect()->route('configuracion.catalogos.index')->with('status_congress', 'Congreso eliminado correctamente.');
    }
}
