<?php

namespace App\Http\Controllers\Nomina;

use App\Http\Controllers\Controller;
use App\Models\PayrollConcept;
use App\Models\PayrollRule;
use App\Models\PayrollSetting;
use App\Models\User;
use App\Services\Asistencia\ServicioDeAsistencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Lo que RH configura: sueldos y conceptos por persona, y las reglas de bonos y descuentos. */
class ConfiguracionNominaController extends Controller
{
    public function __construct(private ServicioDeAsistencia $asistencia)
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
        $data = $request->validate([
            'kind' => ['required', 'in:bono,descuento'],
            'name' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'note' => ['nullable', 'string', 'max:255'],
        ], ['name.required' => 'Ponle un nombre al concepto.', 'amount.required' => 'Escribe el monto.', 'valid_until.after_or_equal' => 'La vigencia termina antes de empezar.']);

        PayrollConcept::create($data + ['user_id' => $user->id, 'recurrence' => 'cada_periodo', 'created_by' => $request->user()->id]);

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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'kind' => ['required', 'in:bono,descuento'],
            'conditions' => ['required', 'array', 'min:1', 'max:5'],
            'conditions.*.metric' => ['required', 'in:'.implode(',', array_keys(PayrollRule::METRICAS))],
            'conditions.*.scope' => ['required', 'in:periodo,mes'],
            'conditions.*.op' => ['required', 'in:'.implode(',', array_keys(PayrollRule::OPERADORES))],
            'conditions.*.value' => ['required', 'numeric', 'min:0', 'max:100000'],
            'repeat_mode' => ['required', 'in:una_vez,por_cada'],
            'repeat_metric' => ['required_if:repeat_mode,por_cada', 'nullable', 'in:'.implode(',', array_keys(PayrollRule::METRICAS))],
            'repeat_every' => ['required_if:repeat_mode,por_cada', 'nullable', 'integer', 'min:1', 'max:1000'],
            'repeat_max' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'calc' => ['required', 'in:'.implode(',', array_keys(PayrollRule::CALCULOS))],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'timing' => ['required', 'in:periodo,cierre_mes'],
            'scope' => ['required', 'in:todos,incluir,excluir'],
            'user_ids' => ['required_unless:scope,todos', 'nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'active' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Ponle un nombre a la regla.',
            'conditions.required' => 'Agrega al menos una condición.',
            'conditions.*.value.required' => 'Falta el valor de una condición.',
            'repeat_every.required_if' => 'Indica cada cuántas veces se repite.',
            'repeat_metric.required_if' => 'Elige qué se cuenta para repetir la regla.',
            'amount.required' => 'Escribe el monto o los días.',
            'user_ids.required_unless' => 'Marca a qué personas aplica.',
        ]);

        // Una condición que mira el mes completo solo se puede decidir cuando el mes termina
        $conds = array_values(array_map(fn ($c) => ['metric' => $c['metric'], 'scope' => $c['scope'], 'op' => $c['op'], 'value' => (float) $c['value']], $data['conditions']));
        $usaMes = collect($conds)->contains(fn ($c) => $c['scope'] === 'mes');
        $timing = $usaMes ? 'cierre_mes' : $data['timing'];

        if ($data['calc'] === 'pct_sueldo' && $data['amount'] > 100) {
            throw ValidationException::withMessages(['amount' => 'Un porcentaje no puede pasar de 100.']);
        }

        $campos = [
            'name' => $data['name'], 'description' => $data['description'] ?? null, 'kind' => $data['kind'],
            'conditions' => $conds,
            'repeat_mode' => $data['repeat_mode'],
            'repeat_metric' => $data['repeat_mode'] === 'por_cada' ? $data['repeat_metric'] : null,
            'repeat_every' => $data['repeat_mode'] === 'por_cada' ? $data['repeat_every'] : null,
            'repeat_max' => $data['repeat_mode'] === 'por_cada' ? ($data['repeat_max'] ?? null) : null,
            'calc' => $data['calc'], 'amount' => $data['amount'], 'timing' => $timing,
            'scope' => $data['scope'], 'user_ids' => $data['scope'] === 'todos' ? null : array_map('intval', $data['user_ids'] ?? []),
            'active' => (bool) ($data['active'] ?? false),
        ];

        if ($regla && $regla->exists) {
            $regla->update($campos);
            $msg = "Regla «{$regla->name}» actualizada.";
        } else {
            $regla = PayrollRule::create($campos + ['sort' => (int) PayrollRule::max('sort') + 1, 'created_by' => $request->user()->id]);
            $msg = "Regla «{$regla->name}» creada.";
        }

        return redirect()->route('nomina.reglas')->with('status', $msg.($usaMes && $data['timing'] !== 'cierre_mes' ? ' Como mira el mes completo, se aplica al cerrar el mes.' : ''));
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
