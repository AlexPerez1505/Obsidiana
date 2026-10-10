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
        $query = User::withCount('loginLogs')->with('roles', 'employeeDocuments');

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
     * Reactiva una cuenta baneada (le devuelve el acceso).
     */
    public function unban(User $user): RedirectResponse
    {
        $this->cuentas->desbanear($user);

        return back()->with('status', "Reactivaste la cuenta de {$user->name}. Se le notificó por correo.");
    }
}
