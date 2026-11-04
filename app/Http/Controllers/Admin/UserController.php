<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\Administracion\CuentasDeUsuario;
use App\Services\SessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Panel de usuarios. Lo que cambia una cuenta (aprobar, desactivar, admin,
 * datos de RH) vive en CuentasDeUsuario, que comparte con la API.
 */
class UserController extends Controller
{
    public function __construct(private CuentasDeUsuario $cuentas)
    {
    }

    /**
     * Lista de todos los usuarios con métricas.
     */
    public function index(SessionManager $sessions, Request $request): View
    {
        // Los eliminados con historial ya no aparecen
        $query = User::withCount('loginLogs')->with('roles', 'employeeDocuments')
            ->where('status', '!=', User::STATUS_DELETED);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('payroll_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($position = $request->get('position')) {
            $query->where('position', $position);
        }

        $users = $query->orderByDesc('created_at')->get();

        $activeCounts = $sessions->activeCountsForAll();

        $positions = User::whereNotNull('position')
            ->where('position', '!=', '')
            ->distinct()
            ->pluck('position')
            ->sort()
            ->values();

        return view('admin.users.index', [
            'users'        => $users,
            'activeCounts' => $activeCounts,
            'positions'    => $positions,
            'roles'        => Role::orderBy('label')->get(),
            'filters'      => $request->only(['search', 'status', 'position']),
        ]);
    }

    /**
     * Completa/edita los datos de RH (puesto, teléfono, nómina, datos personales,
     * contactos de emergencia y roles) de un usuario que ya está registrado en
     * el sistema. No crea cuentas nuevas: solo puede seleccionarse entre los
     * usuarios existentes.
     */
    public function updateHrProfile(Request $request): RedirectResponse
    {
        $data = $request->validate(
            ['user_id' => ['required', 'exists:users,id']]
            + CuentasDeUsuario::REGLAS_RH
            + CuentasDeUsuario::REGLAS_ROLES
            + CuentasDeUsuario::REGLAS_DOCUMENTOS
        );

        $user = User::findOrFail($data['user_id']);

        // La foto nueva reemplaza a la anterior; el archivo viejo se borra
        // para no llenar el disco de retratos que ya no se usan.
        $guardarFoto = function () use ($request, $user) {
            if (! $request->hasFile('avatar')) {
                return;
            }
            $disco = Storage::disk(config('filesystems.fotos_disk', 'public'));
            $anterior = $user->avatar;
            $path = $request->file('avatar')->store("avatars/{$user->id}", config('filesystems.fotos_disk', 'public'));
            abort_unless($path, 500, 'No se pudo guardar la foto.');
            $user->forceFill(['avatar' => $path])->save();
            if ($anterior && $anterior !== $path) {
                DB::afterCommit(fn () => $disco->delete($anterior));
            }
        };

        // El formulario es completo: lo que no viene se limpia.
        $rh = [];
        foreach (CuentasDeUsuario::CAMPOS_RH as $campo) {
            $rh[$campo] = $data[$campo] ?? null;
        }
        $rh['vacaciones_disponibles'] = $data['vacaciones_disponibles'] ?? 0;

        // Con quién hace el cambio: sin eso, cualquiera con usuarios.editar
        // podía darse a sí mismo (u otorgar) el rol de administrador.
        DB::transaction(function () use ($user, $rh, $data, $request) {
            $this->cuentas->actualizarPerfilRh($user, $rh);
            $this->cuentas->sincronizarRoles($user, $data['roles'] ?? [], $request->user());
        });

        $guardarFoto();

        foreach (CuentasDeUsuario::DOCUMENTOS as $field => $documentName) {
            if ($request->hasFile($field)) {
                $this->cuentas->guardarDocumento($user, $documentName, $request->file($field));
            }
        }

        return back()->with('status', "Datos de RH actualizados para {$user->name}.");
    }

    /**
     * Detalle de un usuario con su historial de conexiones.
     */
    public function show(User $user, SessionManager $sessions): View
    {
        $documents = $user->employeeDocuments()->latest()->get();
        $shifts = $user->employeeShifts()->orderByDesc('shift_date')->limit(10)->get();
        $shiftDates = $user->employeeShifts()
            ->where('shift_date', '>=', now()->startOfMonth())
            ->where('shift_date', '<=', now()->endOfMonth())
            ->get();

        return view('admin.users.show', [
            'user'        => $user,
            'logs'        => $user->loginLogs()->limit(50)->get(),
            'activeCount' => $sessions->activeCountFor($user->id),
            'documents'   => $documents,
            'shifts'      => $shifts,
            'shiftDates'  => $shiftDates,
        ]);
    }

    public function documento(User $user, int $documento, Request $request)
    {
        $doc = $user->employeeDocuments()->findOrFail($documento);
        $disk = Storage::disk('local');
        if (! $doc->file_path || ! $disk->exists($doc->file_path)) {
            $disk = Storage::disk('public');
        }
        abort_unless($doc->file_path && $disk->exists($doc->file_path), 404);

        $extension = pathinfo($doc->file_path, PATHINFO_EXTENSION);
        $nombre = \Illuminate\Support\Str::slug($doc->name) ?: 'documento';
        $nombre .= $extension ? '.'.$extension : '';
        $mime = $disk->mimeType($doc->file_path);
        $inline = ! $request->boolean('descargar')
            && in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);

        return $disk->response($doc->file_path, $nombre, [
            'Content-Type' => $mime ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], $inline ? 'inline' : 'attachment');
    }

    /**
     * Da o quita el rol de administrador.
     */
    public function toggleAdmin(User $user, Request $request): RedirectResponse
    {
        if ($rechazo = $this->cuentas->rechazoAlCambiarAdmin($user, $request->user())) {
            return back()->with('status', $rechazo);
        }

        $this->cuentas->alternarAdmin($user);

        return back()->with('status', 'Rol actualizado para '.$user->name.'.');
    }

    /**
     * Aprueba una cuenta pendiente para darle acceso.
     */
    public function approve(User $user): RedirectResponse
    {
        $this->cuentas->aprobar($user);

        return back()->with('status', "Aprobaste el acceso de {$user->name}. Se le notificó por correo.");
    }

    /**
     * Banea / desactiva una cuenta y cierra sus sesiones activas (también
     * los tokens de la API).
     */
    public function ban(User $user, Request $request): RedirectResponse
    {
        if ($rechazo = $this->cuentas->rechazoAlBanear($user, $request->user())) {
            return back()->with('status', $rechazo);
        }

        $data = $request->validate([
            'banned_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->cuentas->banear($user, $data['banned_reason'] ?? null);

        return back()->with('status', "Desactivaste la cuenta de {$user->name}. Se le notificó por correo.");
    }

    /**
     * Elimina una cuenta. Sin historial se borra por completo; con historial
     * se retira (sale del sistema) y lo que registró conserva su nombre.
     */
    public function destroy(User $user, Request $request): RedirectResponse
    {
        if ($rechazo = $this->cuentas->rechazoAlEliminar($user, $request->user())) {
            return back()->withErrors(['usuario' => $rechazo]);
        }

        $nombre = $user->name;
        $completo = $this->cuentas->eliminar($user, $request->user());

        return redirect()->route('admin.users.index')->with('status', $completo
            ? "Eliminaste la cuenta de {$nombre}."
            : "Eliminaste la cuenta de {$nombre}. Ya no puede entrar ni aparece en el sistema; sus ventas, checadas y demás registros conservan su nombre.");
    }

    /**
     * Reactiva una cuenta baneada (le devuelve el acceso).
     */
    public function unban(User $user): RedirectResponse
    {
        $this->cuentas->desbanear($user);

        return back()->with('status', "Reactivaste la cuenta de {$user->name}. Se le notificó por correo.");
    }
}
