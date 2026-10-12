<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\PayrollConcept;
use App\Models\PayrollRule;
use App\Models\PayrollSetting;
use App\Models\User;
use App\Services\Asistencia\ServicioDeAsistencia;
use App\Services\Nomina\ConfiguracionDeNomina;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Lo que RH configura: sueldos y conceptos por persona, y las reglas de bonos y descuentos. */
class ConfiguracionNominaController extends Controller
{
    public function __construct(private ServicioDeAsistencia $asistencia, private ConfiguracionDeNomina $nomina)
    {
    }

    // ---------------- Empleados y sueldos ----------------

    public function empleados(): View
    {
        $usuarios = User::where('status', User::STATUS_APPROVED)->orderBy('name')->get();
        $conceptos = PayrollConcept::whereIn('user_id', $usuarios->pluck('id'))->orderBy('kind')->orderBy('name')->get()->groupBy('user_id');

        return view('nomina.empleados', [
            'usuarios' => $usuarios,
            'conceptos' => $conceptos,
            'conHorario' => $this->asistencia->empleados()->pluck('id')->flip(),
            'sinSueldo' => $usuarios->whereNull('sueldo_quincenal')->count(),
        ]);
    }

    public function guardarSueldo(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['sueldo_quincenal' => ['nullable', 'numeric', 'min:0', 'max:9999999']]);

        $user->forceFill(['sueldo_quincenal' => $data['sueldo_quincenal'] ?? null])->save();

        return back()->with('status', "Sueldo de {$user->name} guardado.")->with('abrir', 'u'.$user->id);
    }

    public function guardarConcepto(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(ConfiguracionDeNomina::reglasConceptoFijo(), ConfiguracionDeNomina::mensajesConceptoFijo());

        $this->nomina->crearConceptoFijo($user, $data, $request->user()->id);

        return back()->with('status', ($data['kind'] === 'bono' ? 'Bono' : 'Descuento')." fijo agregado a {$user->name}.")->with('abrir', 'u'.$user->id);
    }

    public function eliminarConcepto(PayrollConcept $concepto): RedirectResponse
    {
        $uid = $concepto->user_id;
        $concepto->delete();

        return back()->with('status', 'Concepto eliminado.')->with('abrir', 'u'.$uid);
    }

    // ---------------- Reglas ----------------

    public function reglas(): View
    {
        return view('nomina.reglas', [
            'reglas' => PayrollRule::orderBy('sort')->orderBy('id')->get(),
            'usuarios' => User::where('status', User::STATUS_APPROVED)->orderBy('name')->get(['id', 'name']),
            'metricas' => PayrollRule::METRICAS,
            'operadores' => PayrollRule::OPERADORES,
            'calculos' => PayrollRule::CALCULOS,
            'diasSueldo' => PayrollSetting::diasSueldo(),
        ]);
    }

    public function guardarRegla(Request $request, ?PayrollRule $regla = null): RedirectResponse
    {
        $data = $request->validate(ConfiguracionDeNomina::reglasRegla(), ConfiguracionDeNomina::mensajesRegla());

        [$regla, $nueva, $alCierre] = $this->nomina->guardarRegla($data, $regla, $request->user()->id);
        $msg = $nueva ? "Regla «{$regla->name}» creada." : "Regla «{$regla->name}» actualizada.";

        return redirect()->route('nomina.reglas')->with('status', $msg.($alCierre ? ' Como mira el mes completo, se aplica al cerrar el mes.' : ''));
    }

    public function alternarRegla(PayrollRule $regla): RedirectResponse
    {
        $regla->update(['active' => ! $regla->active]);

        return back()->with('status', "«{$regla->name}» ".($regla->active ? 'activada' : 'desactivada').'.');
    }

    public function eliminarRegla(PayrollRule $regla): RedirectResponse
    {
        $nombre = $regla->name;
        $regla->delete();

        return redirect()->route('nomina.reglas')->with('status', "Regla «{$nombre}» eliminada.");
    }

    public function guardarAjustes(Request $request): RedirectResponse
    {
        $data = $request->validate(['dias_sueldo' => ['required', 'integer', 'between:1,31']]);
        PayrollSetting::guardar('dias_sueldo', $data['dias_sueldo']);

        return back()->with('status', 'Un día de sueldo ahora es el sueldo de la quincena ÷ '.$data['dias_sueldo'].'.');
    }
}
