<?php

namespace App\Http\Controllers\Api\V1\Administracion;

use App\Http\Controllers\Api\V1\Concerns\ListadoApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Administracion\ConexionResource;
use App\Http\Resources\Api\V1\Administracion\DocumentoEmpleadoResource;
use App\Http\Resources\Api\V1\Administracion\UsuarioPanelResource;
use App\Models\User;
use App\Services\Administracion\CuentasDeUsuario;
use App\Services\Administracion\RolesYPermisos;
use App\Services\SessionManager;
use App\Support\CatalogoPermisos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/**
 * Panel de usuarios: quién entra al sistema, su estado y sus datos de RH.
 *
 * Mismos permisos que la web: ver (`usuarios.ver`), aprobar o desactivar
 * (`usuarios.aprobar`), editar datos, roles y permisos (`usuarios.editar`).
 * Dar o quitar el admin es solo de administradores con acceso total.
 *
 * @tags Administración · Usuarios
 */
class UsuarioController extends Controller
{
    use ListadoApi;

    public function __construct(private CuentasDeUsuario $cuentas)
    {
    }

    /**
     * Listar usuarios.
     *
     * Con sus roles, cuántas veces han entrado y cuántas sesiones web
     * tienen abiertas ahora. Filtros: `buscar` (nombre, correo, nómina o
     * teléfono), `status` (pending, approved, banned) y `position`.
     */
    public function index(Request $request, SessionManager $sesiones): AnonymousResourceCollection
    {
        $request->validate([
            'buscar' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:pending,approved,banned'],
            'position' => ['nullable', 'string', 'max:255'],
        ]);

        $query = User::withCount('loginLogs')->with('roles');

        if ($buscar = $request->query('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('name', 'like', "%{$buscar}%")
                    ->orWhere('email', 'like', "%{$buscar}%")
                    ->orWhere('payroll_number', 'like', "%{$buscar}%")
                    ->orWhere('phone', 'like', "%{$buscar}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($position = $request->query('position')) {
            $query->where('position', $position);
        }

        $usuarios = $this->ordenar($query, $request, ['id', 'name', 'email', 'created_at', 'status'], '-created_at')
            ->paginate($this->porPagina($request));

        $activas = $sesiones->activeCountsForAll();
        $usuarios->getCollection()->each(fn (User $u) => $u->sesiones_activas = $activas[$u->id] ?? 0);

        return UsuarioPanelResource::collection($usuarios);
    }

    /**
     * Ver un usuario.
     *
     * Sus datos de RH, roles, documentos del expediente y sesiones web
     * abiertas. Las conexiones van aparte en `/usuarios/{user}/conexiones`.
     */
    public function show(User $user, SessionManager $sesiones): UsuarioPanelResource
    {
        $user->load(['roles', 'employeeDocuments' => fn ($q) => $q->latest()])->loadCount('loginLogs');
        $user->sesiones_activas = $sesiones->activeCountFor($user->id);

        return new UsuarioPanelResource($user);
    }

    /**
     * Historial de conexiones.
     *
     * Los inicios de sesión del usuario (web y app), del más reciente al
     * más viejo.
     */
    public function conexiones(Request $request, User $user): AnonymousResourceCollection
    {
        return ConexionResource::collection(
            $user->loginLogs()->paginate($this->porPagina($request))
        );
    }

    /**
     * Editar datos de RH y roles.
     *
     * Solo cambia lo que se manda (puesto, cargo, teléfono, nómina, datos
     * personales, contactos de emergencia). `roles` deja al usuario
     * exactamente con esos roles (ids); el rol de administrador solo lo da
     * o lo quita un administrador con acceso total, y nadie se lo cambia a
     * sí mismo. Los documentos se suben en `/usuarios/{user}/documentos`.
     */
    public function update(Request $request, User $user): UsuarioPanelResource
    {
        $reglas = collect(CuentasDeUsuario::REGLAS_RH + CuentasDeUsuario::REGLAS_ROLES)
            ->map(fn (array $r, string $campo) => str_contains($campo, '.') ? $r : ['sometimes', ...$r])
            ->all();

        $data = $request->validate($reglas);

        if (array_key_exists('vacaciones_disponibles', $data)) {
            $data['vacaciones_disponibles'] ??= 0;
        }

        $this->cuentas->actualizarPerfilRh($user, $data);

        if (array_key_exists('roles', $data)) {
            $this->cuentas->sincronizarRoles($user, $data['roles'] ?? [], $request->user());
        }

        return (new UsuarioPanelResource($user->fresh()->load('roles')))
            ->additional(['message' => "Datos de RH actualizados para {$user->name}."]);
    }

    /**
     * Subir documentos del expediente.
     *
     * Multipart con uno o varios de: `curp_file`, `ine_file`,
     * `acta_nacimiento_file`, `licencia_file` (PDF, JPG o PNG, máx. 5 MB).
     * Si ya había uno con ese nombre, se reemplaza.
     */
    public function documentos(Request $request, User $user): JsonResponse
    {
        $request->validate(CuentasDeUsuario::REGLAS_DOCUMENTOS);

        $subidos = collect(CuentasDeUsuario::DOCUMENTOS)
            ->filter(fn ($nombre, $campo) => $request->hasFile($campo))
            ->map(fn ($nombre, $campo) => $this->cuentas->guardarDocumento($user, $nombre, $request->file($campo)))
            ->values();

        if ($subidos->isEmpty()) {
            throw ValidationException::withMessages([
                'archivo' => 'Manda al menos un documento: curp_file, ine_file, acta_nacimiento_file o licencia_file.',
            ]);
        }

        return DocumentoEmpleadoResource::collection($subidos)
            ->additional(['message' => "Documentos guardados para {$user->name}."])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Aprobar cuenta.
     *
     * Le da acceso a una cuenta pendiente y le avisa por correo.
     */
    public function aprobar(User $user): UsuarioPanelResource
    {
        $this->cuentas->aprobar($user);

        return (new UsuarioPanelResource($user->load('roles')))
            ->additional(['message' => "Aprobaste el acceso de {$user->name}. Se le notificó por correo."]);
    }

    /**
     * Desactivar cuenta.
     *
     * Le quita el acceso, cierra todas sus sesiones (web y app), revoca sus
     * llaves de la API y le avisa por correo. Nadie se desactiva a sí
     * mismo.
     */
    public function banear(Request $request, User $user): UsuarioPanelResource
    {
        if ($rechazo = $this->cuentas->rechazoAlBanear($user, $request->user())) {
            abort(403, $rechazo);
        }

        $data = $request->validate([
            'banned_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->cuentas->banear($user, $data['banned_reason'] ?? null);

        return (new UsuarioPanelResource($user->load('roles')))
            ->additional(['message' => "Desactivaste la cuenta de {$user->name}. Se le notificó por correo."]);
    }

    /**
     * Reactivar cuenta.
     *
     * Le devuelve el acceso a una cuenta desactivada y le avisa por correo.
     */
    public function desbanear(User $user): UsuarioPanelResource
    {
        $this->cuentas->desbanear($user);

        return (new UsuarioPanelResource($user->load('roles')))
            ->additional(['message' => "Reactivaste la cuenta de {$user->name}. Se le notificó por correo."]);
    }

    /**
     * Dar o quitar el admin.
     *
     * Alterna la bandera de administrador. Solo administradores con token
     * de acceso total; nadie se lo cambia a sí mismo.
     */
    public function admin(Request $request, User $user): UsuarioPanelResource
    {
        if ($rechazo = $this->cuentas->rechazoAlCambiarAdmin($user, $request->user())) {
            abort(403, $rechazo);
        }

        $this->cuentas->alternarAdmin($user);

        return (new UsuarioPanelResource($user->load('roles')))
            ->additional(['message' => 'Rol actualizado para '.$user->name.'.']);
    }

    /**
     * Permisos de un usuario.
     *
     * Los que tiene concedidos directo (la excepción, sin inventar un rol),
     * los que le llegan por sus roles activos y la suma. `grupos` es el
     * catálogo para pintar la pantalla.
     */
    public function permisos(User $user): JsonResponse
    {
        return response()->json(['data' => $this->resumenDePermisos($user)]);
    }

    /**
     * Cambiar los permisos directos.
     *
     * Deja al usuario exactamente con estas llaves directas (las de sus
     * roles no se tocan aquí). Las que no existen en el catálogo se
     * ignoran.
     */
    public function actualizarPermisos(Request $request, User $user, RolesYPermisos $roles): JsonResponse
    {
        $data = $request->validate([
            'permisos' => ['present', 'array'],
            'permisos.*' => ['string'],
        ]);

        $guardados = $roles->sincronizarDeUsuario($user, $data['permisos']);

        return response()->json([
            'data' => $this->resumenDePermisos($user->fresh()),
            'message' => 'Permisos de '.$user->name.' actualizados: '.$guardados->count().' directo(s).',
        ]);
    }

    private function resumenDePermisos(User $user): array
    {
        $directos = $user->permissions()->pluck('name')->filter(fn ($p) => CatalogoPermisos::existe($p))->values();
        $porRol = $user->roles()->where('is_active', true)->with('permissions')->get()
            ->flatMap(fn ($r) => $r->permissions->pluck('name'))
            ->filter(fn ($p) => CatalogoPermisos::existe($p))
            ->unique()->sort()->values();

        return [
            'user_id' => $user->id,
            'es_administrador' => $user->isAdmin(),
            'directos' => $directos,
            'por_rol' => $porRol,
            'efectivos' => $directos->merge($porRol)->unique()->sort()->values(),
            'grupos' => PermisoController::catalogo(),
        ];
    }
}
