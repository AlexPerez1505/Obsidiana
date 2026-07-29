<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AccountStatusMail;
use App\Models\User;
use App\Services\SessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Lista de todos los usuarios con métricas.
     */
    public function index(SessionManager $sessions): View
    {
        $users = User::withCount('loginLogs')
            ->orderByDesc('created_at')
            ->get();

        $activeCounts = $sessions->activeCountsForAll();

        return view('admin.users.index', [
            'users'        => $users,
            'activeCounts' => $activeCounts,
        ]);
    }

    /**
     * Detalle de un usuario con su historial de conexiones.
     */
    public function show(User $user, SessionManager $sessions): View
    {
        return view('admin.users.show', [
            'user'         => $user,
            'logs'         => $user->loginLogs()->limit(50)->get(),
            'activeCount'  => $sessions->activeCountFor($user->id),
        ]);
    }

    /**
     * Da o quita el rol de administrador.
     */
    public function toggleAdmin(User $user, Request $request): RedirectResponse
    {
        // No permitir que un admin se quite el rol a sí mismo (evita quedarse sin admins por error).
        if ($user->id === $request->user()->id) {
            return back()->with('status', 'No puedes cambiar tu propio rol de administrador.');
        }

        $user->forceFill(['is_admin' => ! $user->is_admin])->save();

        return back()->with('status', 'Rol actualizado para '.$user->name.'.');
    }

    /**
     * Aprueba una cuenta pendiente para darle acceso.
     */
    public function approve(User $user): RedirectResponse
    {
        $user->forceFill([
            'status'        => User::STATUS_APPROVED,
            'banned_reason' => null,
            'approved_at'   => now(),
        ])->save();

        $this->notify($user, 'approved');

        return back()->with('status', "Aprobaste el acceso de {$user->name}. Se le notificó por correo.");
    }

    /**
     * Banea / desactiva una cuenta y cierra sus sesiones activas.
     */
    public function ban(User $user, Request $request): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('status', 'No puedes desactivar tu propia cuenta.');
        }

        $data = $request->validate([
            'banned_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user->forceFill([
            'status'        => User::STATUS_BANNED,
            'banned_reason' => $data['banned_reason'] ?? null,
        ])->save();

        // Expulsar de todos sus dispositivos.
        DB::table('sessions')->where('user_id', $user->id)->delete();

        $this->notify($user, 'banned', $data['banned_reason'] ?? null);

        return back()->with('status', "Desactivaste la cuenta de {$user->name}. Se le notificó por correo.");
    }

    /**
     * Reactiva una cuenta baneada (le devuelve el acceso).
     */
    public function unban(User $user): RedirectResponse
    {
        $user->forceFill([
            'status'        => User::STATUS_APPROVED,
            'banned_reason' => null,
            'approved_at'   => $user->approved_at ?? now(),
        ])->save();

        $this->notify($user, 'reactivated');

        return back()->with('status', "Reactivaste la cuenta de {$user->name}. Se le notificó por correo.");
    }

    /**
     * Envía el correo de notificación de estado (sin romper si el SMTP falla).
     */
    protected function notify(User $user, string $type, ?string $reason = null): void
    {
        try {
            Mail::to($user->email)->send(new AccountStatusMail($type, $user->name, $reason));
        } catch (\Throwable $e) {
            Log::error("No se pudo enviar el correo de estado ({$type}) a {$user->email}: ".$e->getMessage());
        }
    }
}
