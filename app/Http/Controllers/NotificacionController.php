<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** La campana: abrir una notificación la marca leída y lleva a su pantalla. */
class NotificacionController extends Controller
{
    public function abrir(Request $request, string $id): RedirectResponse
    {
        $n = $request->user()->notifications()->whereKey($id)->first();

        if (! $n) {
            return redirect()->route('dashboard');
        }

        $n->markAsRead();

        return redirect()->to($n->data['url'] ?? route('dashboard'));
    }

    public function leerTodas(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'Notificaciones marcadas como leídas.');
    }
}
