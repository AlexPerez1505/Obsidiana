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
            'categories' => Category::query()->orderBy('name')->get(),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    /**
     * Guarda un nuevo congreso.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,svg,bmp,pdf,doc,docx,xls,xlsx,ppt,pptx'],
            'category_id' => ['required', 'exists:categories,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'assembly_time' => ['required', 'date_format:H:i'],
            'disassembly_time' => ['required', 'date_format:H:i'],
            'download_access' => ['required', 'boolean'],
            'download_text' => ['nullable', 'string'],
            'upload_access' => ['required', 'boolean'],
            'upload_text' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'comments' => ['nullable', 'string', 'max:5000'],
            'notify_users' => ['nullable', 'array'],
            'notify_users.*' => ['exists:users,id'],
        ]);

        $notifyUsers = $request->input('notify_users', []);
        unset($data['notify_users']);

        if ($request->hasFile('images')) {
            $data['image_path'] = [];
            foreach ($request->file('images') as $file) {
                $data['image_path'][] = $file->store('congresses', 'public');
            }
        }

        $congress = Congress::create($data);

        if (!empty($notifyUsers)) {
            $congress->notifiedUsers()->sync($notifyUsers);
        }

        return redirect()->route('configuracion.catalogos.index')->with('status_congress', 'Congreso guardado correctamente.');
    }

    /**
     * Muestra el formulario para editar un congreso.
     */
    public function edit(Congress $congress): View
    {
        return view('structure.Configuracion.Catalogos.Congresos.u_congresos', [
            'congress' => $congress,
            'categories' => Category::query()->orderBy('name')->get(),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    /**
     * Actualiza un congreso existente.
     */
    public function update(Request $request, Congress $congress): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,svg,bmp,pdf,doc,docx,xls,xlsx,ppt,pptx'],
            'category_id' => ['required', 'exists:categories,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'assembly_time' => ['required', 'date_format:H:i'],
            'disassembly_time' => ['required', 'date_format:H:i'],
            'download_access' => ['required', 'boolean'],
            'download_text' => ['nullable', 'string'],
            'upload_access' => ['required', 'boolean'],
            'upload_text' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'comments' => ['nullable', 'string', 'max:5000'],
            'notify_users' => ['nullable', 'array'],
            'notify_users.*' => ['exists:users,id'],
        ]);

        $notifyUsers = $request->input('notify_users', []);
        unset($data['notify_users']);

        $images = $congress->image_path ?? [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $images[] = $file->store('congresses', 'public');
            }
        }
        $data['image_path'] = $images;

        $congress->update($data);

        if (!empty($notifyUsers)) {
            $congress->notifiedUsers()->sync($notifyUsers);
        } else {
            $congress->notifiedUsers()->detach();
        }

        return redirect()->route('configuracion.catalogos.index')->with('status_congress', 'Congreso actualizado correctamente.');
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
