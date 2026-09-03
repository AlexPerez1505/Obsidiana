<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;

use App\Models\Category;
use App\Models\Congress;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CongressController extends Controller
{
    private const CARPETA = 'congresos';

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
        $data = $this->validado($request);

        $data['path_archivo'] = $this->subirArchivos($request);

        Congress::create($data);

        return redirect()->route('configuracion.catalogos.index')->with('status', 'Congreso guardado correctamente.');
    }

    /**
     * Reglas comunes de alta y edicion.
     *
     * Los nombres son los de la base. El formulario los mandaba en ingles
     * (name, category_id, start_date...), asi que nunca pasaba la validacion.
     */
    private function validado(Request $request): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'categoria_id' => ['required', 'exists:categorias,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_finalizacion' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'hora_montaje' => ['required', 'date_format:H:i'],
            'hora_desmontaje' => ['required', 'date_format:H:i'],
            'descarga_acceso' => ['nullable', 'boolean'],
            'descarga_texto' => ['nullable', 'string', 'max:255'],
            'acceso_subir' => ['nullable', 'boolean'],
            'subir_texto' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'comments' => ['nullable', 'string', 'max:5000'],
            'archivos' => ['nullable', 'array', 'max:10'],
            'archivos.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,ppt,pptx'],
            'quitar_archivos' => ['nullable', 'array'],
        ], [
            'archivos.max' => 'Puedes subir hasta 10 archivos a la vez.',
            'archivos.*.mimes' => 'Solo se aceptan imágenes, PDF o documentos de Office.',
        ]);

        $data['descarga_acceso'] = $request->boolean('descarga_acceso');
        $data['acceso_subir'] = $request->boolean('acceso_subir');

        // Los archivos se resuelven aparte.
        unset($data['archivos'], $data['quitar_archivos']);

        return $data;
    }

    /**
     * Guarda los archivos nuevos y devuelve la lista completa.
     *
     * @return array<int, string>
     */
    private function subirArchivos(Request $request, array $previos = []): array
    {
        // Lo que el usuario marcó para quitar sale de la lista y del disco.
        foreach ((array) $request->input('quitar_archivos', []) as $ruta) {
            if (Storage::disk('public')->exists($ruta)) {
                Storage::disk('public')->delete($ruta);
            }

            $previos = array_values(array_diff($previos, [$ruta]));
        }

        foreach ((array) $request->file('archivos', []) as $archivo) {
            $previos[] = $archivo->store(self::CARPETA, 'public');
        }

        return array_values(array_unique($previos));
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
        $data = $this->validado($request);

        // Los archivos que ya tenía se conservan; se suman los nuevos y se
        // restan los que se marcaron para quitar.
        $data['path_archivo'] = $this->subirArchivos($request, (array) ($congress->path_archivo ?? []));

        $congress->update($data);

        return redirect()->route('configuracion.catalogos.index')->with('status', 'Congreso actualizado correctamente.');
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

        return redirect()->route('configuracion.catalogos.index')->with('status', 'Congreso eliminado correctamente.');
    }
}
