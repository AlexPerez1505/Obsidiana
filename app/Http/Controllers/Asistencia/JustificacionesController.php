<?php

namespace App\Http\Controllers\Asistencia;

use App\Http\Controllers\Controller;
use App\Models\AsistenciaJustificacion;
use App\Models\User;
use App\Services\Asistencia\JustificacionesDeAsistencia;
use App\Services\Asistencia\ResumenDeChecadas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * RH pide justificar (a una persona o a todas las que tienen problema ese
 * día), la persona responde desde su aviso y RH acepta o rechaza.
 */
class JustificacionesController extends Controller
{
    public function __construct(private JustificacionesDeAsistencia $justificaciones, private ResumenDeChecadas $resumen) {}

    /** Pide a una o varias personas que justifiquen el día. */
    public function pedir(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ], ['user_ids.required' => 'Elige a quién pedirle la justificación.']);

        $dia = Carbon::parse($data['fecha'])->startOfDay();
        $filas = $this->resumen->del($dia)->keyBy(fn ($f) => $f['user']->id);

        $pedidas = 0;
        $sinCorreo = [];
        $omitidas = 0;

        foreach (array_unique($data['user_ids']) as $id) {
            $f = $filas->get((int) $id);

            // Solo se pide lo que de verdad falta y no se ha pedido ya.
            if (! $f || ! $f['puede_pedir']) {
                $omitidas++;

                continue;
            }

            $ok = $this->justificaciones->pedir($f['user'], $dia, $f['estado'], $f['frase'], $request->user());
            $pedidas++;
            if (! $ok) {
                $sinCorreo[] = $f['user']->name;
            }
        }

        if ($pedidas === 0) {
            return back()->withErrors(['justificacion' => 'No había nada que pedir: ya se pidió o el día está completo.']);
        }

        $msg = $pedidas === 1
            ? 'Se le pidió justificar a '.$filas->get((int) $data['user_ids'][0])['user']->name.'. Le llegó a sus avisos y a su correo.'
            : "Se pidió justificar a {$pedidas} personas. Les llegó a sus avisos y a su correo.";

        if ($sinCorreo) {
            $msg .= ' El correo no salió para: '.implode(', ', $sinCorreo).' (sí lo ven en sus avisos).';
        }

        return back()->with('status', $msg);
    }

    public function aceptar(Request $request, AsistenciaJustificacion $justificacion): RedirectResponse
    {
        $data = $request->validate(['nota' => ['nullable', 'string', 'max:255']]);

        if ($justificacion->estado !== AsistenciaJustificacion::RESPONDIDA) {
            return back()->withErrors(['justificacion' => 'Solo se puede aceptar una justificación que la persona ya respondió.']);
        }

        $this->justificaciones->aceptar($justificacion, $request->user(), $data['nota'] ?? null);

        return back()->with('status', 'Justificación aceptada. Se le avisó a '.$justificacion->user->name.'.');
    }

    public function rechazar(Request $request, AsistenciaJustificacion $justificacion): RedirectResponse
    {
        $data = $request->validate(['nota' => ['nullable', 'string', 'max:255']]);

        if (! $justificacion->abierta()) {
            return back()->withErrors(['justificacion' => 'Esta justificación ya se revisó.']);
        }

        $this->justificaciones->rechazar($justificacion, $request->user(), $data['nota'] ?? null);

        return back()->with('status', 'Justificación rechazada. Se le avisó a '.$justificacion->user->name.'.');
    }

    /** Lo que ve la persona al abrir su aviso. */
    public function mostrar(Request $request, AsistenciaJustificacion $justificacion): View
    {
        $this->soloDueno($request->user(), $justificacion);

        $fila = $this->resumen->del($justificacion->work_date->copy(), $justificacion->user_id)->first();

        $otras = AsistenciaJustificacion::where('user_id', $justificacion->user_id)
            ->whereKeyNot($justificacion->id)
            ->latest('work_date')->limit(10)->get();

        return view('asistencia.justificar', [
            'j' => $justificacion->load(['solicitante', 'revisor']),
            'fila' => $fila,
            'otras' => $otras,
        ]);
    }

    public function responder(Request $request, AsistenciaJustificacion $justificacion): RedirectResponse
    {
        $this->soloDueno($request->user(), $justificacion);

        $data = $request->validate(JustificacionesDeAsistencia::REGLAS_RESPUESTA, JustificacionesDeAsistencia::MENSAJES_RESPUESTA);

        $this->justificaciones->responder($justificacion, $data);

        return back()->with('status', 'Listo, tu justificación se envió a RH. Te avisamos cuando la revisen.');
    }

    private function soloDueno(User $user, AsistenciaJustificacion $j): void
    {
        abort_unless($j->user_id === $user->id, 403, 'Esta justificación es de otra persona.');
    }
}
