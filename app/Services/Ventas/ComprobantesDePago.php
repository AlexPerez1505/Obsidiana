<?php

namespace App\Services\Ventas;

use App\Models\Cobro;
use App\Models\CobroSolicitud;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaBitacora;
use App\Notifications\PagoPorAprobar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Pagos por aprobar.
 *
 *   • El vendedor sube el pago SIEMPRE con su comprobante (transferencia,
 *     depósito… o la foto del recibo si fue en efectivo). Queda PENDIENTE y
 *     no cuenta en el saldo.
 *   • Lo valida una sola persona según la venta (config medibuy.cobranza):
 *     la de ventas sin IVA o la de ventas con IVA, escribiendo su NIP (o su
 *     contraseña). Sin esa configuración, como antes: administradores o
 *     quien tenga NIP. Ahí nace el cobro real con su recibo, con la misma
 *     lógica de siempre (CobrosDeVenta).
 *   • O lo rechaza con un motivo; el vendedor lo ve y puede volver a subirlo.
 */
class ComprobantesDePago
{
    public const CARPETA = 'cobros/comprobantes';

    public function __construct(private readonly CobrosDeVenta $cobros)
    {
    }

    /**
     * ¿Puede validar pagos? Con $venta: los de esa venta. Sin ella: los de
     * alguna venta (para enseñar o no la lista de "por aprobar").
     */
    public static function puedeAprobar(?User $user, ?Venta $venta = null): bool
    {
        if ($user === null) {
            return false;
        }

        if (self::hayValidadores()) {
            return $venta
                ? (int) $user->id === (int) self::validadorDe($venta)
                : in_array((int) $user->id, array_map('intval', array_filter(self::validadores())), true);
        }

        return $user->isAdmin() || filled($user->approval_pin_hash);
    }

    /** @return array{sin_iva: ?int, con_iva: ?int} */
    public static function validadores(): array
    {
        $c = config('medibuy.cobranza', []);

        return [
            'sin_iva' => filled($c['valida_sin_iva'] ?? null) ? (int) $c['valida_sin_iva'] : null,
            'con_iva' => filled($c['valida_con_iva'] ?? null) ? (int) $c['valida_con_iva'] : null,
        ];
    }

    public static function hayValidadores(): bool
    {
        return (bool) array_filter(self::validadores());
    }

    /** Quién valida los pagos de esta venta: la de "con IVA" o la de "sin IVA". */
    public static function validadorDe(Venta $venta): ?int
    {
        $v = self::validadores();

        return $venta->cobraConIva() ? ($v['con_iva'] ?? $v['sin_iva']) : ($v['sin_iva'] ?? $v['con_iva']);
    }

    /** "Gabriela (ventas sin IVA)": para los avisos en pantalla. */
    public static function quienValida(Venta $venta): string
    {
        if (! self::hayValidadores()) {
            return 'un administrador o alguien con NIP';
        }

        $nombre = User::whereKey(self::validadorDe($venta))->value('name') ?? 'quien valida';

        return $nombre.' (ventas '.($venta->cobraConIva() ? 'con' : 'sin').' IVA)';
    }

    /** El aviso cuando alguien que no valida intenta aprobar o registrar directo. */
    public static function soloValida(Venta $venta): string
    {
        return 'Solo '.self::quienValida($venta).' valida los pagos de esta venta. Sube el pago con su comprobante para que lo valide.';
    }

    public static function reglas(Venta $venta): array
    {
        return [
            'venta_pago_id' => ['nullable', Rule::exists('venta_pagos', 'id')->where('venta_id', $venta->id)],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'metodo' => ['required', Rule::in(array_keys(Cobro::METODOS))],
            'referencia' => ['nullable', 'string', 'max:255'],
            'nota' => ['nullable', 'string', 'max:255'],
            // Sin comprobante no se registra ningún pago (en efectivo, la foto del recibo).
            'archivos' => ['required', 'array', 'min:1', 'max:5'],
            'archivos.*' => ['file', 'mimes:jpg,jpeg,png,webp,heic,pdf', 'max:15360'],
        ];
    }

    public const MENSAJES = [
        'archivos.required' => 'Sube la foto o el PDF del comprobante: sin comprobante no se puede registrar el pago (en efectivo, la foto del recibo).',
        'archivos.min' => 'Sube la foto o el PDF del comprobante: sin comprobante no se puede registrar el pago (en efectivo, la foto del recibo).',
        'fecha.before_or_equal' => 'La fecha del pago no puede ser futura.',
    ];

    /** Lo que ya está esperando aprobación en esta venta. */
    public static function pendienteDe(Venta $venta): float
    {
        return (float) CobroSolicitud::where('venta_id', $venta->id)->where('estado', CobroSolicitud::PENDIENTE)->sum('monto');
    }

    /** @param  array<int, UploadedFile>  $archivos */
    public function subir(Venta $venta, array $data, array $archivos, User $user): CobroSolicitud
    {
        $this->cobros->asegurarCobrable($venta);

        $disponible = round($venta->saldo() - self::pendienteDe($venta), 2);
        if ($data['monto'] > $disponible + 0.009) {
            throw ValidationException::withMessages(['monto' => $disponible > 0
                ? 'El monto pasa de lo que falta por pagar ($'.number_format($disponible, 2).', descontando lo que ya está por aprobar).'
                : 'Esta venta no tiene saldo pendiente (o ya hay pagos por aprobar que lo cubren).']);
        }

        $guardados = collect($archivos)->map(fn (UploadedFile $a) => [
            'archivo' => $a->store(self::CARPETA, 'public'),
            'nombre' => mb_substr($a->getClientOriginalName(), 0, 150),
        ])->values()->all();

        $sol = CobroSolicitud::create([
            'venta_id' => $venta->id,
            'venta_pago_id' => $data['venta_pago_id'] ?? null,
            'fecha' => $data['fecha'],
            'monto' => $data['monto'],
            'metodo' => $data['metodo'],
            'referencia' => $data['referencia'] ?? null,
            'nota' => $data['nota'] ?? null,
            'archivos' => $guardados,
            'estado' => CobroSolicitud::PENDIENTE,
            'subido_por' => $user->id,
        ]);

        VentaBitacora::registrar($venta, 'comprobante_subido',
            $user->name.' subió un pago por $'.number_format((float) $sol->monto, 2).' ('.$sol->metodoLabel().') para aprobación',
            ['solicitud_id' => $sol->id]);

        $this->avisarAprobadores($sol);

        return $sol;
    }

    /**
     * Quienes pueden aprobar este pago. Con validadores configurados, solo
     * el de esa venta (sin IVA o con IVA). Si no, administradores y personas
     * con NIP que ven esa venta, con la cuenta activa. Nunca quien lo subió.
     */
    public function aprobadores(CobroSolicitud $sol): Collection
    {
        $venta = $sol->venta;

        if (self::hayValidadores()) {
            return User::whereKey(self::validadorDe($venta))->where('status', User::STATUS_APPROVED)->get();
        }

        return User::query()
            ->where('status', User::STATUS_APPROVED)
            ->whereKeyNot($sol->subido_por)
            ->where(fn ($q) => $q->where('is_admin', true)
                ->orWhereNotNull('approval_pin_hash')
                ->orWhereHas('roles', fn ($r) => $r->where('name', User::ROL_ADMIN)->where('is_active', true)))
            ->get()
            ->filter(fn (User $u) => self::puedeAprobar($u) && $venta->visiblePara($u))
            ->values();
    }

    /**
     * Campana del sistema + correo a cada aprobador. Va después de responder
     * (no hace esperar al vendedor) y un correo que falla no tumba nada.
     */
    private function avisarAprobadores(CobroSolicitud $sol): void
    {
        $sol->loadMissing(['venta.customer', 'subidoPor']);

        dispatch(function () use ($sol) {
            foreach ($this->aprobadores($sol) as $u) {
                try {
                    $u->notify(new PagoPorAprobar($sol));
                } catch (\Throwable $e) {
                    Log::warning('[Comprobantes] No salió el aviso de pago por aprobar', ['user_id' => $u->id, 'error' => $e->getMessage()]);
                }
            }
        })->afterResponse();
    }

    /** @return string mensaje para el usuario */
    public function aprobar(CobroSolicitud $sol, User $user, ?string $nip): string
    {
        if (! self::puedeAprobar($user, $sol->venta)) {
            throw ValidationException::withMessages(['nip' => self::soloValida($sol->venta)]);
        }
        $this->verificarNip($user, $nip);

        return DB::transaction(function () use ($sol, $user) {
            // Candado: dos personas aprobando el mismo pago no crean dos cobros.
            $sol = CobroSolicitud::whereKey($sol->id)->lockForUpdate()->firstOrFail();
            if ($sol->estado !== CobroSolicitud::PENDIENTE) {
                throw ValidationException::withMessages(['nip' => 'Este pago ya se había '.($sol->estado === CobroSolicitud::APROBADO ? 'aprobado' : 'rechazado').'.']);
            }

            $venta = Venta::findOrFail($sol->venta_id);
            try {
                $r = $this->cobros->registrar($venta, [
                    'venta_pago_id' => $sol->venta_pago_id,
                    'fecha' => $sol->fecha->toDateString(),
                    'monto' => (float) $sol->monto,
                    'metodo' => $sol->metodo,
                    'referencia' => $sol->referencia,
                    'nota' => trim(($sol->nota ? $sol->nota.' · ' : '').'Subido por '.($sol->subidoPor?->name ?? 'vendedor').', aprobado por '.$user->name),
                ]);
            } catch (OperacionRechazada $e) {
                throw ValidationException::withMessages(['nip' => $e->getMessage()]);
            }

            /** @var Cobro $cobro */
            $cobro = $r['cobro'];
            foreach ($sol->archivos ?? [] as $a) {
                $cobro->evidencias()->create(['archivo' => $a['archivo'], 'nombre' => $a['nombre'] ?? basename($a['archivo'])]);
            }

            $sol->update([
                'estado' => CobroSolicitud::APROBADO, 'revisado_por' => $user->id, 'revisado_en' => now(), 'cobro_id' => $cobro->id,
            ]);

            VentaBitacora::registrar($venta, 'comprobante_aprobado',
                $user->name.' aprobó el pago de $'.number_format((float) $sol->monto, 2).' → cobro '.$cobro->folio,
                ['solicitud_id' => $sol->id, 'cobro_id' => $cobro->id]);

            return 'Pago aprobado: se registró el cobro '.$cobro->folio.'. '.preg_replace('/^Cobro registrado correctamente\.\s*/', '', $r['mensaje']);
        });
    }

    /*
    | No hacer el trabajo dos veces
    |
    | Si el vendedor ya subió el pago, quien aprueba no debe registrarlo de
    | nuevo en "Registrar pago": se aprueba el que subió. Y si ya se registró
    | por fuera, el que subió se liga a ese cobro (sin crear otro).
    */

    /** Pagos por aprobar de la venta con ese mismo monto. */
    public function pendientesIguales(Venta $venta, float $monto): Collection
    {
        return CobroSolicitud::with('subidoPor:id,name')
            ->where('venta_id', $venta->id)
            ->where('estado', CobroSolicitud::PENDIENTE)
            ->whereBetween('monto', [$monto - 0.009, $monto + 0.009])
            ->get();
    }

    /** El aviso cuando se intenta registrar un pago que ya subió el vendedor. */
    public static function avisoDuplicado(Collection $iguales): string
    {
        $s = $iguales->first();

        return ($s->subidoPor?->name ?? 'El vendedor').' ya subió este pago de $'.number_format((float) $s->monto, 2)
            .' ('.$s->metodoLabel().', '.$s->fecha->format('d/m/Y').') y está en "Pagos por aprobar". '
            .'Apruébalo ahí en lugar de registrarlo otra vez; si de verdad es otro pago distinto, confírmalo.';
    }

    /**
     * Cobros ya registrados de la venta que pueden ser este mismo pago: del
     * mismo monto y que no vengan de otro pago subido.
     */
    public function cobrosParaLigar(CobroSolicitud $sol): Collection
    {
        $ligados = CobroSolicitud::whereNotNull('cobro_id')->pluck('cobro_id');

        return Cobro::where('venta_id', $sol->venta_id)
            ->whereNotIn('id', $ligados)
            ->whereBetween('monto', [(float) $sol->monto - 0.009, (float) $sol->monto + 0.009])
            ->latest('id')
            ->get();
    }

    /**
     * "Ya lo registré": el pago subido queda aprobado y ligado al cobro que
     * ya existía. No se crea otro cobro ni se mueve el saldo; el comprobante
     * se suma a la evidencia de ese cobro.
     */
    public function ligar(CobroSolicitud $sol, Cobro $cobro, User $user): string
    {
        if (! self::puedeAprobar($user, $sol->venta)) {
            throw ValidationException::withMessages(['cobro_id' => self::soloValida($sol->venta)]);
        }

        return DB::transaction(function () use ($sol, $cobro, $user) {
            $sol = CobroSolicitud::whereKey($sol->id)->lockForUpdate()->firstOrFail();
            if ($sol->estado !== CobroSolicitud::PENDIENTE) {
                throw ValidationException::withMessages(['cobro_id' => 'Este pago ya no está por aprobar.']);
            }
            if (! $this->cobrosParaLigar($sol)->contains('id', $cobro->id)) {
                throw ValidationException::withMessages(['cobro_id' => 'Ese cobro no es de esta venta, no es del mismo monto o ya está ligado a otro pago.']);
            }

            foreach ($sol->archivos ?? [] as $a) {
                $cobro->evidencias()->create(['archivo' => $a['archivo'], 'nombre' => $a['nombre'] ?? basename($a['archivo'])]);
            }

            $sol->update([
                'estado' => CobroSolicitud::APROBADO, 'revisado_por' => $user->id, 'revisado_en' => now(), 'cobro_id' => $cobro->id,
            ]);

            VentaBitacora::registrar($sol->venta, 'comprobante_ligado',
                $user->name.' marcó el pago subido de $'.number_format((float) $sol->monto, 2).' como ya registrado en el cobro '.$cobro->folio.' (no se cobró dos veces)',
                ['solicitud_id' => $sol->id, 'cobro_id' => $cobro->id]);

            return 'Listo: el pago que subió '.($sol->subidoPor?->name ?? 'el vendedor').' quedó ligado al cobro '.$cobro->folio.'. No se registró otro cobro.';
        });
    }

    public function rechazar(CobroSolicitud $sol, User $user, string $motivo): string
    {
        if (! self::puedeAprobar($user, $sol->venta)) {
            throw ValidationException::withMessages(['motivo' => self::soloValida($sol->venta)]);
        }
        if ($sol->estado !== CobroSolicitud::PENDIENTE) {
            throw ValidationException::withMessages(['motivo' => 'Este pago ya no está por aprobar.']);
        }

        $sol->update(['estado' => CobroSolicitud::RECHAZADO, 'revisado_por' => $user->id, 'revisado_en' => now(), 'motivo_rechazo' => mb_substr($motivo, 0, 500)]);
        VentaBitacora::registrar($sol->venta, 'comprobante_rechazado',
            $user->name.' rechazó el pago de $'.number_format((float) $sol->monto, 2).': '.$motivo, ['solicitud_id' => $sol->id]);

        return 'Pago rechazado. El vendedor verá el motivo.';
    }

    /** El vendedor puede quitar un pago suyo mientras siga por aprobar. */
    public function retirar(CobroSolicitud $sol, User $user): string
    {
        if ($sol->estado !== CobroSolicitud::PENDIENTE || ((int) $sol->subido_por !== (int) $user->id && ! self::puedeAprobar($user, $sol->venta))) {
            throw ValidationException::withMessages(['monto' => 'Ese pago ya no se puede quitar.']);
        }
        Storage::disk('public')->delete(collect($sol->archivos ?? [])->pluck('archivo')->all());
        $sol->delete();

        return 'Se quitó el pago por aprobar.';
    }

    private function verificarNip(User $user, ?string $nip): void
    {
        $nip = (string) $nip;
        $conPin = filled($user->approval_pin_hash);
        if ($nip === '') {
            throw ValidationException::withMessages(['nip' => 'Escribe tu '.($conPin ? 'NIP' : 'contraseña').' para aprobar.']);
        }
        $ok = $conPin ? $user->checkApprovalPin($nip) : Hash::check($nip, $user->password);
        if (! $ok) {
            throw ValidationException::withMessages(['nip' => $conPin ? 'NIP incorrecto.' : 'Contraseña incorrecta.']);
        }
    }
}
