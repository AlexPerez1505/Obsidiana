{{--
    Pagos por aprobar.
      $venta          → bloque de una venta (lista + botón "Subir pago").
      $solicitudes    → lista general (Cobranza), sin botón de subir.
    Incluye los diálogos de subir, aprobar (con NIP) y rechazar.
--}}
@php
    use App\Models\CobroSolicitud;
    use App\Services\Ventas\ComprobantesDePago;

    $yo = auth()->user();
    $aprueba = ComprobantesDePago::puedeAprobar($yo);
    $conPin = filled($yo->approval_pin_hash);
    $deVenta = isset($venta) && ! isset($solicitudes);
    $lista = $solicitudes ?? CobroSolicitud::with(['subidoPor:id,name', 'revisadoPor:id,name', 'cobro:id,folio', 'parcialidad:id,nombre'])
        ->where('venta_id', $venta->id)->latest('id')->get();
    $pendientes = $lista->where('estado', CobroSolicitud::PENDIENTE);
    $puedeSubir = $deVenta && ! $aprueba && ! $venta->cancelada() && $yo->can('cobranza.comprobantes') && $venta->saldo() > 0.009;
    // Quien aprueba registra directo desde Cobranza; en la venta solo se le liga ahí.
    $irACobranza = $deVenta && $aprueba && empty($enCobranza) && ! $venta->cancelada() && $venta->saldo() > 0.009;
@endphp

<div class="erp-card pad cp-card" id="pagos-por-aprobar">
    <div class="cp-head">
        <h3>Pagos por aprobar
            @if ($pendientes->isNotEmpty())<span class="cp-n">{{ $pendientes->count() }} · ${{ number_format($pendientes->sum('monto'), 2) }}</span>@endif
        </h3>
        @if ($puedeSubir)
            <button type="button" class="erp-btn sm" data-cp-abrir="cpSubir">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Registrar pago
            </button>
        @elseif ($irACobranza)
            <a href="{{ route('commercial.ventas.cobros.index', $venta) }}?registrar=1" class="erp-btn sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Registrar pago
            </a>
        @endif
    </div>
    @unless ($aprueba)
        <p class="cp-nota">Los pagos que subas no cuentan en el saldo hasta que un administrador o alguien con NIP confirme que se vio reflejado (o que recibió el efectivo).</p>
    @endunless

    @forelse ($lista as $s)
        <div class="cp-fila es-{{ $s->estado }}">
            <div class="cp-txt">
                <div class="cp-top">
                    <b>${{ number_format((float) $s->monto, 2) }}</b>
                    <span class="erp-badge {{ $s->tono() }}"><span class="dot"></span>{{ $s->estadoLabel() }}</span>
                    <span class="cp-met">{{ $s->metodoLabel() }}</span>
                </div>
                <div class="cp-det">
                    @unless ($deVenta)
                        <a href="{{ route('commercial.ventas.cobros.index', $s->venta_id) }}">{{ $s->venta?->folio }}</a> · {{ trim(($s->venta?->customer?->nombre ?? '').' '.($s->venta?->customer?->apellido ?? '')) }} ·
                    @endunless
                    Pagó el {{ $s->fecha->format('d/m/Y') }}{{ $s->parcialidad ? ' · '.$s->parcialidad->nombre : '' }}{{ $s->referencia ? ' · Ref. '.$s->referencia : '' }}
                    · Subió {{ $s->subidoPor?->name ?? '—' }} {{ $s->created_at->diffForHumans() }}
                </div>
                @if ($s->nota)<div class="cp-det">“{{ $s->nota }}”</div>@endif
                @if ($s->estado === CobroSolicitud::RECHAZADO)
                    <div class="cp-det cp-mal">Rechazado por {{ $s->revisadoPor?->name }}: {{ $s->motivo_rechazo }}</div>
                @elseif ($s->estado === CobroSolicitud::APROBADO)
                    <div class="cp-det cp-ok">Aprobado por {{ $s->revisadoPor?->name }} el {{ $s->revisado_en?->format('d/m/Y H:i') }}{{ $s->cobro ? ' · Cobro '.$s->cobro->folio : '' }}</div>
                @endif
                @if ($s->archivos)
                    <div class="cp-archivos">
                        @foreach ($s->archivosUrls() as $a)
                            <a href="{{ $a['url'] }}" target="_blank" rel="noopener" title="{{ $a['nombre'] }}">
                                @if ($a['imagen'])<img src="{{ $a['url'] }}" alt="Comprobante" loading="lazy">@else<span class="cp-pdf">PDF</span>@endif
                            </a>
                        @endforeach
                    </div>
                @elseif ($s->metodo === 'efectivo')
                    <div class="cp-det">Efectivo sin foto de recibo.</div>
                @endif
            </div>
            @if ($s->estado === CobroSolicitud::PENDIENTE)
                <div class="cp-acc">
                    @if ($aprueba)
                        <button type="button" class="erp-btn sm" data-cp-sol="{{ $s->id }}" data-cp-aprobar="{{ route('commercial.comprobantes.aprobar', $s) }}"
                                data-cp-desc="${{ number_format((float) $s->monto, 2) }} · {{ $s->metodoLabel() }}{{ $s->venta ? ' · '.$s->venta->folio : '' }}">Aprobar</button>
                        <button type="button" class="erp-btn ghost sm" data-cp-rechazar="{{ route('commercial.comprobantes.rechazar', $s) }}">Rechazar</button>
                        {{-- Ya se capturó en "Registrar pago": se liga a ese cobro y no se cobra dos veces. --}}
                        @php $yaRegistrados = app(ComprobantesDePago::class)->cobrosParaLigar($s); @endphp
                        @if ($yaRegistrados->isNotEmpty())
                            <form method="POST" action="{{ route('commercial.comprobantes.ligar', $s) }}" style="margin:0;"
                                  data-confirm="Este pago quedará aprobado como el cobro que ya registraste. No se crea otro cobro ni cambia el saldo. ¿Continuar?">
                                @csrf
                                <input type="hidden" name="cobro_id" value="{{ $yaRegistrados->first()->id }}">
                                <button type="submit" class="erp-btn ghost sm" title="Ya está registrado como {{ $yaRegistrados->first()->folio }} (${{ number_format((float) $yaRegistrados->first()->monto, 2) }}, {{ $yaRegistrados->first()->fecha->format('d/m/Y') }})">
                                    Ya lo registré · {{ $yaRegistrados->first()->folio }}
                                </button>
                            </form>
                        @endif
                    @endif
                    @if ((int) $s->subido_por === (int) $yo->id || $aprueba)
                        <form method="POST" action="{{ route('commercial.comprobantes.destroy', $s) }}" data-confirm="¿Quitar este pago por aprobar?" style="margin:0;">
                            @csrf @method('DELETE')
                            <button type="submit" class="erp-btn ghost sm" title="Quitar">Quitar</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    @empty
        <p class="cp-vacio">{{ $deVenta ? 'Sin pagos subidos.' : 'No hay pagos esperando aprobación.' }}</p>
    @endforelse
</div>

{{-- ===================== Subir pago ===================== --}}
@if ($puedeSubir)
    <dialog class="cp-dlg" id="cpSubir" aria-labelledby="cpSubirT">
        <form method="POST" action="{{ route('commercial.comprobantes.store', $venta) }}" enctype="multipart/form-data">
            @csrf
            <div class="cp-dh" id="cpSubirT">Registrar pago · {{ $venta->folio }}</div>
            <div class="cp-db">
                @if ($errors->comprobante->any())
                    <div class="cp-err">@foreach ($errors->comprobante->all() as $e)<div>{{ $e }}</div>@endforeach</div>
                @endif
                <div class="cp-r2">
                    <label>Monto
                        <input type="number" name="monto" step="0.01" min="0.01" required value="{{ old('monto') }}"
                               max="{{ round($venta->saldo() - ComprobantesDePago::pendienteDe($venta), 2) }}" placeholder="0.00">
                    </label>
                    <label>Fecha del pago
                        <input type="date" name="fecha" required value="{{ old('fecha', now()->toDateString()) }}" max="{{ now()->toDateString() }}">
                    </label>
                </div>
                <div class="cp-r2">
                    <label>Método
                        <select name="metodo" required data-cp-metodo>
                            @foreach (\App\Models\Cobro::METODOS as $k => $v)
                                <option value="{{ $k }}" @selected(old('metodo', 'transferencia') === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </label>
                    @if ($venta->pagos->isNotEmpty())
                        <label>Parcialidad <small>(opcional)</small>
                            <select name="venta_pago_id">
                                <option value="">La más antigua pendiente</option>
                                @foreach ($venta->pagos->filter(fn ($p) => $p->saldo() > 0) as $p)
                                    <option value="{{ $p->id }}" @selected((string) old('venta_pago_id') === (string) $p->id)>{{ $p->nombre }} · ${{ number_format($p->saldo(), 2) }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                </div>
                <label>Referencia o folio <small>(opcional)</small><input type="text" name="referencia" maxlength="255" value="{{ old('referencia') }}"></label>
                <label>Nota <small>(opcional)</small><input type="text" name="nota" maxlength="255" value="{{ old('nota') }}" placeholder="Ej. lo depositó la secretaria del doctor"></label>
                <label><span data-cp-arch-t>Comprobante</span> <small data-cp-arch-s>(foto o PDF, hasta 5)</small>
                    <input type="file" name="archivos[]" multiple accept="image/*,application/pdf" data-cp-archivos>
                </label>
                <p class="cp-nota" style="margin:4px 0 0;">Queda <b>por aprobar</b>: no se suma al saldo hasta que alguien con NIP lo confirme.</p>
            </div>
            <div class="cp-df">
                <button type="button" class="erp-btn ghost" data-cp-cerrar>Cancelar</button>
                <button type="submit" class="erp-btn">Enviar para aprobación</button>
            </div>
        </form>
    </dialog>
@endif

{{-- ===================== Aprobar / rechazar ===================== --}}
@if ($aprueba && $lista->where('estado', CobroSolicitud::PENDIENTE)->isNotEmpty())
    <dialog class="cp-dlg" id="cpAprobar" aria-labelledby="cpAprobarT">
        <form method="POST">
            @csrf
            <div class="cp-dh" id="cpAprobarT">Aprobar pago</div>
            <div class="cp-db">
                <p style="margin:0 0 10px;" data-cp-desc></p>
                <p class="cp-nota" style="margin:0 0 12px;">Aprueba solo si ya viste el dinero reflejado en la cuenta o lo recibiste en efectivo. Se registrará el cobro con su recibo.</p>
                <label>{{ $conPin ? 'Tu NIP de aprobación' : 'Tu contraseña' }}
                    <input type="password" name="nip" required autocomplete="off" inputmode="{{ $conPin ? 'numeric' : 'text' }}">
                </label>
            </div>
            <div class="cp-df">
                <button type="button" class="erp-btn ghost" data-cp-cerrar>Cancelar</button>
                <button type="submit" class="erp-btn">Aprobar y registrar cobro</button>
            </div>
        </form>
    </dialog>
    <dialog class="cp-dlg" id="cpRechazar" aria-labelledby="cpRechazarT">
        <form method="POST">
            @csrf
            <div class="cp-dh" id="cpRechazarT">Rechazar pago</div>
            <div class="cp-db">
                <label>Motivo (lo verá el vendedor)
                    <textarea name="motivo" required maxlength="500" rows="3" placeholder="Ej. no aparece en la cuenta; el monto no coincide"></textarea>
                </label>
            </div>
            <div class="cp-df">
                <button type="button" class="erp-btn ghost" data-cp-cerrar>Cancelar</button>
                <button type="submit" class="erp-btn danger">Rechazar</button>
            </div>
        </form>
    </dialog>
@endif

<style>
    .cp-card { margin-bottom:18px; }
    .cp-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:8px; }
    .cp-head h3 { margin:0; font-size:15px; font-weight:650; display:flex; align-items:center; gap:8px; }
    .cp-n { font-size:12px; font-weight:650; padding:2px 8px; border-radius:999px; background:var(--accent-soft); color:var(--accent); }
    .cp-nota { font-size:12.5px; color:var(--muted); margin:0 0 10px; }
    .cp-fila { display:flex; gap:12px; padding:10px 0; border-top:1px solid var(--border); align-items:flex-start; }
    .cp-txt { flex:1; min-width:0; }
    .cp-top { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .cp-top b { font-size:15px; }
    .cp-met { font-size:12.5px; color:var(--muted); }
    .cp-det { font-size:12.5px; color:var(--muted); margin-top:3px; }
    .cp-mal { color:var(--danger); } .cp-ok { color:var(--green); }
    .cp-archivos { display:flex; gap:6px; flex-wrap:wrap; margin-top:6px; }
    .cp-archivos a { display:block; width:56px; height:56px; border-radius:8px; overflow:hidden; border:1px solid var(--border); background:var(--surface-2); }
    .cp-archivos img { width:100%; height:100%; object-fit:cover; }
    .cp-pdf { display:flex; align-items:center; justify-content:center; height:100%; font-size:11px; font-weight:700; color:var(--muted); }
    .cp-acc { display:flex; flex-direction:column; gap:6px; align-items:stretch; }
    .cp-vacio { margin:6px 0 0; font-size:13px; color:var(--muted); }
    dialog.cp-dlg { border:0; border-radius:12px; padding:0; width:min(480px, calc(100vw - 32px)); background:var(--surface); color:var(--text); box-shadow:0 20px 60px rgba(0,0,0,.25); }
    dialog.cp-dlg::backdrop { background:rgba(15,23,42,.45); }
    .cp-dh { padding:14px 18px; border-bottom:1px solid var(--border); font-weight:650; font-size:15px; }
    .cp-db { padding:14px 18px; display:flex; flex-direction:column; gap:10px; font-size:13.5px; }
    .cp-db label { display:flex; flex-direction:column; gap:5px; font-weight:600; font-size:12.5px; }
    .cp-db label small { font-weight:400; color:var(--muted); }
    .cp-db input, .cp-db select, .cp-db textarea { padding:9px 10px; border:1px solid var(--border); border-radius:8px; background:var(--surface); color:var(--text); font:inherit; font-size:14px; font-weight:400; }
    .cp-r2 { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    @media (max-width:520px) { .cp-r2 { grid-template-columns:1fr; } .cp-fila { flex-direction:column; } .cp-acc { flex-direction:row; flex-wrap:wrap; } }
    .cp-df { padding:12px 18px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:8px; }
    .cp-err { padding:8px 10px; border-radius:8px; background:var(--danger-soft); color:var(--danger); font-size:12.5px; }
</style>
<script>
(function () {
    if (window.__cpListo) return; window.__cpListo = true;
    const abrir = id => document.getElementById(id)?.showModal();
    document.addEventListener('click', function (e) {
        const a = e.target.closest('[data-cp-abrir]');
        if (a) { abrir(a.dataset.cpAbrir); return; }
        const ap = e.target.closest('[data-cp-aprobar]');
        if (ap) {
            const d = document.getElementById('cpAprobar');
            d.querySelector('form').action = ap.dataset.cpAprobar;
            d.querySelector('[data-cp-desc]').textContent = ap.dataset.cpDesc;
            d.querySelector('[name=nip]').value = '';
            d.showModal(); d.querySelector('[name=nip]').focus(); return;
        }
        const re = e.target.closest('[data-cp-rechazar]');
        if (re) { const d = document.getElementById('cpRechazar'); d.querySelector('form').action = re.dataset.cpRechazar; d.showModal(); return; }
        if (e.target.closest('[data-cp-cerrar]')) e.target.closest('dialog').close();
        if (e.target.matches('dialog.cp-dlg')) e.target.close();
    });
    // En efectivo la foto del recibo es opcional.
    document.addEventListener('change', function (e) {
        if (!e.target.matches('[data-cp-metodo]')) return;
        const f = e.target.closest('form'), ef = e.target.value === 'efectivo';
        f.querySelector('[data-cp-arch-t]').textContent = ef ? 'Foto del recibo' : 'Comprobante';
        f.querySelector('[data-cp-arch-s]').textContent = ef ? '(opcional)' : '(foto o PDF, hasta 5)';
        f.querySelector('[data-cp-archivos]').required = !ef;
    });
    document.querySelectorAll('[data-cp-metodo]').forEach(s => s.dispatchEvent(new Event('change', { bubbles: true })));
    @if ($errors->comprobante->any()) document.getElementById('cpSubir')?.showModal(); @endif
})();
</script>
