<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\TokenApi;
use App\Models\User;
use App\Support\CatalogoPermisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Configuración → API e integraciones.
 *
 * Aquí se emiten las llaves fijas para que otros sistemas (Jureto, la
 * página web, n8n…) entren a /api/v1, y se ven/cierran las sesiones que
 * los usuarios abrieron desde apps.
 *
 * Una llave siempre actúa a nombre de un usuario: puede lo que ese usuario
 * pueda, recortado a los permisos que se le marquen. El administrador
 * emite a nombre de cualquiera; los demás, solo a nombre propio.
 */
class ApiLlavesController extends Controller
{
    public function index(Request $request): View
    {
        $yo = $request->user();

        $tokens = TokenApi::query()
            ->with(['tokenable', 'creadoPor'])
            ->when(! $yo->isAdmin(), fn ($q) => $q->where('tokenable_type', $yo->getMorphClass())->where('tokenable_id', $yo->id))
            ->latest()
            ->get();

        return view('structure.Configuracion.api.index', [
            'llaves' => $tokens->where('tipo', TokenApi::TIPO_INTEGRACION)->values(),
            'sesiones' => $tokens->where('tipo', TokenApi::TIPO_APP)->sortByDesc('last_used_at')->values(),
            'grupos' => CatalogoPermisos::grupos(),
            'usuarios' => $yo->isAdmin()
                ? User::where('status', User::STATUS_APPROVED)->orderBy('name')->get(['id', 'name', 'email'])
                : collect([$yo]),
            'urlBase' => url('/api/v1'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $yo = $request->user();

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'acceso' => ['required', Rule::in(['total', 'limitado'])],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['string'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'ips' => ['nullable', 'string', 'max:1000'],
        ], [
            'expires_at.after' => 'La fecha de vencimiento debe ser posterior a hoy.',
        ], [
            'name' => 'nombre',
            'user_id' => 'usuario',
            'expires_at' => 'vencimiento',
            'ips' => 'IP permitidas',
        ]);

        $dueno = $yo->isAdmin() && ! empty($datos['user_id'])
            ? User::findOrFail($datos['user_id'])
            : $yo;

        if (! $dueno->isApproved()) {
            throw ValidationException::withMessages(['user_id' => 'Ese usuario no tiene acceso al sistema: su llave no serviría.']);
        }

        $marcados = collect($datos['permisos'] ?? [])->filter(fn (string $p) => CatalogoPermisos::existe($p))->unique();

        // Solo lo que el dueño de verdad tiene: marcar algo que él no puede
        // daría una llave que promete de más (y que igual no funcionaría).
        $habilidades = $datos['acceso'] === 'total'
            ? ['*']
            : $marcados->filter(fn (string $p) => $dueno->hasPermission($p))->values()->all();

        if ($habilidades === []) {
            throw ValidationException::withMessages(['permisos' => 'Marca al menos un permiso (de los que tiene el usuario) o elige acceso total.']);
        }

        $omitidos = $datos['acceso'] === 'total' ? 0 : $marcados->count() - count($habilidades);

        $nueva = TokenApi::emitir(
            $dueno,
            $datos['name'],
            TokenApi::TIPO_INTEGRACION,
            $habilidades,
            ! empty($datos['expires_at']) ? Carbon::parse($datos['expires_at'])->endOfDay() : null,
            $this->ips($datos['ips'] ?? ''),
            $yo,
        );

        $aviso = "Se creó la llave «{$datos['name']}». Cópiala ahora: no se vuelve a mostrar.";
        if ($omitidos > 0) {
            $aviso .= " Se dejaron fuera {$omitidos} permiso(s) que {$dueno->name} no tiene.";
        }

        // El texto completo solo existe ahora: se muestra una vez y ya.
        return redirect()->route('configuracion.api.index')
            ->with('llave_nueva', ['nombre' => $datos['name'], 'texto' => $nueva->plainTextToken])
            ->with('status', $aviso);
    }

    /** Revoca una llave o cierra la sesión de una app. */
    public function destroy(Request $request, TokenApi $token): RedirectResponse
    {
        $yo = $request->user();

        $esMio = $token->tokenable_type === $yo->getMorphClass() && (int) $token->tokenable_id === $yo->id;
        abort_unless($yo->isAdmin() || $esMio, 403, 'Esa llave es de otro usuario.');

        $token->delete();

        return back()->with('status', $token->esIntegracion()
            ? "Se revocó la llave «{$token->name}». El sistema que la usaba ya no puede entrar."
            : "Se cerró la sesión de «{$token->name}».");
    }

    /**
     * "10.0.0.1, 200.1.2.3" o una por renglón → lista limpia.
     *
     * @return list<string>|null
     */
    private function ips(string $texto): ?array
    {
        $ips = collect(preg_split('/[\s,;]+/', $texto, -1, PREG_SPLIT_NO_EMPTY))->unique()->values();

        $malas = $ips->reject(fn (string $ip) => filter_var($ip, FILTER_VALIDATE_IP));
        if ($malas->isNotEmpty()) {
            throw ValidationException::withMessages(['ips' => 'No son IP válidas: '.$malas->implode(', ').'.']);
        }

        return $ips->isEmpty() ? null : $ips->all();
    }
}
