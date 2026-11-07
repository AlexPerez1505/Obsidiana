@extends('structure.commercial_management.erp')

@section('title', $campana->nombre)
@section('page-title', 'Marketing y promociones')

@php
    use App\Models\CampanaDestinatario as D;
    $estadosFiltro = [D::PENDIENTE, D::ENVIADO, D::ENTREGADO, D::LEIDO, D::FALLO, D::SIMULADO, D::OMITIDO_CONSENTIMIENTO, D::OMITIDO_FRECUENCIA, D::CANCELADO];
    $nAud = $audiencia ? $audiencia['elegibles']->count() : null;
@endphp

@section('erp_content')
@include('structure.promociones._estilos')
<div class="mk mkt" data-no-mayus>
    @include('structure.promociones._shell', ['tab' => 'campanas'])
    <div style="display:flex; align-items:center; gap:10px; margin:0 0 12px;">
        <a href="{{ route('promociones.campanas.index') }}" class="mk-ico bordered" aria-label="Volver a campañas"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg></a>
        <h2 style="margin:0; font-size:17px; font-weight:650; flex:1; min-width:0;">{{ $campana->nombre }}</h2>
        <span class="mk-badge {{ $campana->tonoEstado() }}">{{ $campana->estadoLabel() }}</span>
    </div>

    @if ($errors->any())
        <div class="mkt-aviso warn" style="margin-bottom:16px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    {{-- ============ Acciones ============ --}}
    <div class="mkt-acciones" style="margin-bottom:18px;">
        @can('promociones.enviar')
            @if ($campana->puedeEnviarse())
                <button type="button" class="erp-btn" data-abrir="dlgEnviar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    {{ $campana->estado === 'programada' ? 'Enviar ahora o reprogramar' : 'Programar o enviar' }}
                </button>
            @endif
            @if ($campana->estado !== 'cancelada')
                <button type="button" class="erp-btn ghost" data-abrir="dlgPrueba">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                    Enviar prueba
                </button>
            @endif
            @if ($campana->puedePausarse())
                <form method="POST" action="{{ route('promociones.campanas.pausar', $campana) }}">@csrf
                    <button class="erp-btn ghost" type="submit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>Pausar</button>
                </form>
            @endif
            @if ($campana->puedeReanudarse())
                <form method="POST" action="{{ route('promociones.campanas.reanudar', $campana) }}">@csrf
                    <button class="erp-btn ghost" type="submit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>Reanudar</button>
                </form>
            @endif
        @endcan
        @can('promociones.gestionar')
            @if ($campana->editable())
                <a href="{{ route('promociones.campanas.edit', $campana) }}" class="erp-btn ghost"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>Editar</a>
            @endif
            <form method="POST" action="{{ route('promociones.campanas.duplicar', $campana) }}">@csrf
                <button class="erp-btn ghost" type="submit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>Duplicar</button>
            </form>
            @if ($campana->puedeCancelarse())
                <form method="POST" action="{{ route('promociones.campanas.cancelar', $campana) }}"
                      data-confirm="Se cancelará la campaña «{{ $campana->nombre }}». Los mensajes que aún no salen ya no se enviarán y el enlace dejará de aceptar pedidos. ¿Continuar?"
                      data-confirm-title="Cancelar campaña" data-confirm-ok="Sí, cancelar" data-confirm-danger="1">@csrf
                    <button class="erp-btn ghost" type="submit" style="color:var(--danger);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>Cancelar</button>
                </form>
            @endif
        @endcan
    </div>

    @if ($campana->estado === 'programada' && $campana->programada_para)
        <div class="mkt-aviso info" style="margin-bottom:16px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <div>Se enviará automáticamente el <b>{{ $campana->programada_para->format('d/m/Y \a \l\a\s H:i') }}</b>. La lista final se arma en ese momento (con los consentimientos de ese día).</div>
        </div>
    @endif

    {{-- ============ Resultados ============ --}}
    <div class="mkt-kpis">
        <div class="mkt-kpi"><div class="l">Destinatarios</div><div class="n mkt-num">{{ number_format($r['destinatarios']) }}</div>
            <div class="s">{{ $r['pendientes'] }} en cola · {{ $r['omitidos'] }} omitidos</div></div>
        <div class="mkt-kpi"><div class="l">Enviados</div><div class="n mkt-num">{{ number_format($r['enviados']) }}</div>
            <div class="s">{{ $r['simulados'] ? $r['simulados'].' simulados (no salieron)' : 'aceptados por WhatsApp' }}</div></div>
        <div class="mkt-kpi"><div class="l">Entregados</div><div class="n mkt-num">{{ number_format($r['entregados']) }}</div>
            <div class="s">{{ $r['tasa_entrega'] !== null ? $r['tasa_entrega'].' % de los enviados' : 'según el webhook' }}</div></div>
        <div class="mkt-kpi"><div class="l">Leídos</div><div class="n mkt-num">{{ number_format($r['leidos']) }}</div><div class="s">si el cliente tiene confirmación de lectura</div></div>
        <div class="mkt-kpi"><div class="l">Fallidos</div><div class="n mkt-num" style="{{ $r['fallidos'] ? 'color:var(--danger)' : '' }}">{{ number_format($r['fallidos']) }}</div><div class="s">ver motivo en la tabla</div></div>
        <div class="mkt-kpi"><div class="l">Clics en el enlace</div><div class="n mkt-num">{{ number_format($r['clics_unicos']) }}</div>
            <div class="s">{{ $r['clics'] }} aperturas{{ $r['tasa_clic'] !== null ? ' · '.$r['tasa_clic'].' %' : '' }}</div></div>
        <div class="mkt-kpi"><div class="l">Pedidos desde el enlace</div><div class="n mkt-num">{{ number_format($r['pedidos']) }}</div><div class="s">{{ $r['cupones_reservados'] }} cupón(es) reservados</div></div>
        <div class="mkt-kpi"><div class="l">Cupones usados</div><div class="n mkt-num">{{ number_format($r['cupones_usados']) }}</div><div class="s">${{ number_format($r['descontado'], 2) }} descontados</div></div>
        <div class="mkt-kpi"><div class="l">Ventas atribuidas</div><div class="n mkt-num">{{ number_format($r['ventas']) }}</div><div class="s">${{ number_format($r['ingresos'], 2) }} en ventas</div></div>
    </div>

    <div class="mkt-grid">
        <div class="mkt-col">
            <section class="mkt-card">
                <div class="mkt-card-h"><h3>Destinatarios</h3></div>
                <form class="mkt-filtros" method="GET">
                    <input type="search" name="q" value="{{ $buscar }}" class="mkt-in crece" placeholder="Buscar cliente">
                    <select name="estado" class="mkt-in" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        @foreach ($estadosFiltro as $e)
                            <option value="{{ $e }}" @selected($filtroEstado === $e)>{{ (new D(['estado' => $e]))->estadoLabel() }}</option>
                        @endforeach
                        <option value="prueba" @selected($filtroEstado === 'prueba')>Pruebas</option>
                    </select>
                    <button class="erp-btn ghost sm" type="submit">Filtrar</button>
                </form>
                <div class="erp-table-wrap">
                    <table class="erp-table">
                        <thead><tr><th>Cliente</th><th>Teléfono</th><th>Estado</th><th>Clics</th><th>Cupón</th><th>Último movimiento</th></tr></thead>
                        <tbody>
                        @forelse ($destinatarios as $d)
                            <tr>
                                <td class="erp-strong">
                                    @if ($d->cliente)
                                        <a href="{{ route('commercial.clientes.show', $d->cliente) }}" style="color:inherit;">{{ trim($d->cliente->nombre.' '.$d->cliente->apellido) }}</a>
                                    @else
                                        <span class="mkt-muted">Prueba sin cliente</span>
                                    @endif
                                    @if ($d->es_prueba)<div class="mkt-muted" style="font-size:11.5px; font-weight:400;">prueba · <a href="{{ $d->enlace() }}" target="_blank" rel="noopener">abrir enlace</a></div>@endif
                                </td>
                                <td class="mkt-num mkt-muted">{{ $d->telefonoOculto() }}</td>
                                <td>
                                    <span class="erp-badge {{ $d->tono() }}"><span class="dot"></span>{{ $d->estadoLabel() }}</span>
                                    @if ($d->error && in_array($d->estado, [D::FALLO, D::PENDIENTE], true))
                                        <div style="font-size:11.5px; color:var(--danger); margin-top:3px; max-width:260px;">{{ $d->error }}@if($d->error_codigo) ({{ $d->error_codigo }})@endif</div>
                                    @endif
                                </td>
                                <td class="mkt-num">{{ $d->clics ?: '—' }}</td>
                                <td>@if ($d->cupon)<span class="mkt-codigo">{{ $d->cupon->codigo }}</span>@else<span class="mkt-muted">—</span>@endif</td>
                                <td class="mkt-muted" style="font-size:12.5px;">
                                    {{ ($d->leido_en ?? $d->entregado_en ?? $d->fallo_en ?? $d->enviado_en ?? $d->updated_at)?->format('d/m H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="erp-empty" style="padding:22px; text-align:center;" class="mkt-muted">
                                {{ $campana->puedeEnviarse() ? 'La lista se arma al enviar. Abajo a la derecha ves cuántos la recibirían hoy.' : 'Sin destinatarios con ese filtro.' }}
                            </div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($destinatarios->hasPages())
                    <div class="mkt-pie">@include('partials._paginacion', ['paginator' => $destinatarios])</div>
                @endif
            </section>

            <section class="mkt-card">
                <div class="mkt-card-h"><h3>Bitácora</h3></div>
                <div class="mkt-card-b">
                    @if ($eventos->isEmpty())
                        <p class="ayuda">Sin movimientos.</p>
                    @else
                        <ul class="mkt-tl">
                            @foreach ($eventos as $ev)
                                <li>{{ $ev->detalle }}<small>{{ $ev->created_at->format('d/m/Y H:i') }} · {{ $ev->usuario?->name ?? 'Sistema / cliente' }}</small></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        </div>

        <div class="mkt-col">
            <section class="mkt-card">
                <div class="mkt-card-h"><h3>Vista previa</h3></div>
                <div class="mkt-card-b">
                    @php $enviada = $campana->iniciada_en; @endphp
                    <div class="mk-wa"><span class="mk-badge {{ $enviada ? 'ok' : 'neutral' }} etq">{{ $enviada ? 'Enviado '.$enviada->format('d/m H:i') : 'Vista previa' }}</span>
                        <div class="mk-wa-b {{ $enviada ? '' : 'borrador' }}" style="margin-top:18px;">
                            @if (config('promociones.whatsapp.plantilla_con_imagen'))<img src="{{ $campana->imagenUrl() ?? asset('images/logo.png') }}" alt="">@endif
                            <div class="mk-wa-t">{{ $vistaPrevia }}</div>
                            <div class="mk-wa-h">@if($enviada){{ $enviada->format('H:i') }} <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="18 7 9.5 15.5 6 12"/><polyline points="22 7 13.5 15.5"/></svg>@else sin enviar @endif</div>
                            <div class="mk-wa-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>{{ config('promociones.whatsapp.plantilla_boton_texto') }}</div>
                            @if (config('promociones.whatsapp.plantilla_boton_baja'))<div class="mk-wa-btn">Ya no quiero promociones</div>@endif
                        </div>
                    </div>
                    <p class="ayuda" style="margin-top:6px;">{{ $enviada ? 'Así salió, con el nombre y código de cada cliente.' : 'Cada cliente recibe su nombre y su código.' }}</p>
                    <p class="ayuda" style="margin-top:8px;">Plantilla: <span class="mkt-codigo">{{ $campana->plantillaWhatsapp() }}</span></p>
                </div>
            </section>

            @if ($audiencia)
                <section class="mkt-card">
                    <div class="mkt-card-h"><h3>Recibirían la campaña hoy</h3></div>
                    <div class="mkt-card-b">
                        <div class="mkt-aud"><b>{{ number_format($nAud) }}</b><span class="mkt-muted">clientes</span></div>
                        <div class="mkt-excl">
                            <span>{{ \App\Services\Promociones\Audiencia::describir($campana->filtros ?? []) }}</span><b>{{ $audiencia['base'] }}</b>
                            <span>Sin consentimiento</span><b>{{ $audiencia['excluidos']['sin_consentimiento'] }}</b>
                            <span>Dados de baja</span><b>{{ $audiencia['excluidos']['baja'] }}</b>
                            <span>Sin teléfono válido</span><b>{{ $audiencia['excluidos']['sin_telefono'] }}</b>
                            <span>Número repetido</span><b>{{ $audiencia['excluidos']['duplicado'] }}</b>
                        </div>
                    </div>
                </section>
            @endif

            <section class="mkt-card">
                <div class="mkt-card-h"><h3>Oferta</h3></div>
                <div class="mkt-card-b">
                    <ul class="mkt-list">
                        <li><span class="k">Cupón</span><span class="v">
                            @if ($campana->cupon)
                                <a href="{{ route('promociones.cupones.show', $campana->cupon) }}" class="mkt-codigo">{{ $campana->cupon->codigo }}</a>
                                <div class="mkt-muted" style="font-weight:400; font-size:12px;">{{ $campana->cupon->textoDescuento() }}{{ $campana->cupon_exclusivo ? ' · código personal' : '' }}</div>
                            @else — @endif
                        </span></li>
                        <li><span class="k">Vigencia</span><span class="v">
                            {{ $campana->vigencia_inicio?->format('d/m/Y H:i') ?? 'Desde el envío' }} — {{ $campana->vigencia_fin?->format('d/m/Y H:i') ?? 'sin fecha' }}
                        </span></li>
                        <li><span class="k">Creó</span><span class="v">{{ $campana->creador?->name }} · {{ $campana->created_at->format('d/m/Y') }}</span></li>
                    </ul>
                    <div style="margin-top:10px;">
                        @foreach ($productos as $p)
                            <div class="mkt-prod">
                                @if ($p['imagen'])<img src="{{ $p['imagen'] }}" alt="">@else<span class="ph"></span>@endif
                                <div class="t"><b>{{ $p['nombre'] }}</b><small>{{ $p['modelo'] }} {{ $p['marca'] ? '· '.$p['marca'] : '' }}</small></div>
                                <span class="mkt-num" style="font-size:13px;">${{ number_format($p['precio'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </div>

    {{-- ============ Diálogo: programar / enviar ============ --}}
    @can('promociones.enviar')
    @if ($campana->puedeEnviarse())
        <dialog class="mkt-dlg" id="dlgEnviar">
            <form method="POST" action="{{ route('promociones.campanas.enviar', $campana) }}">
                @csrf
                <div class="mkt-dlg-h">Confirmar envío</div>
                <div class="mkt-dlg-b">
                    <ul class="mkt-list" style="margin-bottom:12px;">
                        <li><span class="k">Campaña</span><span class="v">{{ $campana->nombre }}</span></li>
                        <li><span class="k">Destinatarios</span><span class="v">{{ number_format($nAud ?? 0) }} cliente(s)</span></li>
                        <li><span class="k">Horario</span><span class="v" id="envHorario">Ahora mismo</span></li>
                        <li><span class="k">Modo</span><span class="v">{{ $whatsapp->listoParaEnviar() ? (config('promociones.solo_numeros_prueba') ? 'Real solo a números de prueba; el resto simulado' : 'Envío real por WhatsApp') : 'Simulado (no sale nada)' }}</span></li>
                    </ul>
                    <div class="mkt-seg" style="margin-bottom:12px;">
                        <label><input type="radio" name="cuando" value="ahora" checked> Enviar ahora</label>
                        <label><input type="radio" name="cuando" value="programar"> Programar</label>
                    </div>
                    <div class="mkt-f" id="envFecha" hidden>
                        <label for="programada_para">Fecha y hora</label>
                        <input type="datetime-local" id="programada_para" name="programada_para" class="mkt-in" min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}" value="{{ $campana->programada_para?->format('Y-m-d\TH:i') }}">
                    </div>
                    @if (! $campana->prueba_enviada_en)
                        <div class="mkt-aviso warn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <div>Aún no has mandado una prueba de esta campaña. Te recomendamos revisarla en un número autorizado antes.</div></div>
                    @endif
                    <p class="ayuda" style="margin-top:10px;">Se respetan el consentimiento y la frecuencia ({{ config('promociones.frecuencia_horas') }} h entre promociones) al momento de mandar cada mensaje.</p>
                </div>
                <div class="mkt-dlg-f">
                    <button type="button" class="erp-btn ghost" data-cerrar>Cancelar</button>
                    <button type="submit" class="erp-btn" id="envBtn">Enviar a {{ number_format($nAud ?? 0) }} cliente(s)</button>
                </div>
            </form>
        </dialog>
    @endif

    @if ($campana->estado !== 'cancelada')
        <dialog class="mkt-dlg" id="dlgPrueba">
            <form method="POST" action="{{ route('promociones.campanas.prueba', $campana) }}">
                @csrf
                <div class="mkt-dlg-h">Enviar prueba</div>
                <div class="mkt-dlg-b">
                    @if (empty($numerosPrueba))
                        <div class="mkt-aviso warn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <div>No hay números autorizados. Agrégalos en <span class="mkt-codigo">PROMO_NUMEROS_PRUEBA</span> del archivo .env.</div></div>
                    @else
                        <div class="mkt-f">
                            <label for="telefono">Número autorizado</label>
                            <select name="telefono" id="telefono" class="mkt-in">
                                @foreach ($numerosPrueba as $n)<option value="{{ $n }}">{{ \App\Services\WhatsApp\Telefono::ocultar($n) }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mkt-f">
                            <label for="pruebaCliente">A nombre de un cliente <span class="mkt-muted" style="font-weight:400;">(opcional)</span></label>
                            <div class="mkt-ac">
                                <input id="pruebaCliente" class="mkt-in" placeholder="Buscar cliente…" autocomplete="off">
                                <div id="pruebaClienteLista" class="mkt-ac-list"></div>
                            </div>
                            <input type="hidden" name="cliente_id" id="pruebaClienteId">
                            <p class="ayuda">Con cliente, el enlace permite probar el pedido completo con su cupón (no cuenta en los resultados). Sin cliente, solo se ve la oferta.</p>
                        </div>
                    @endif
                    @if ($pruebas->isNotEmpty())
                        <p class="mkt-lbl" style="margin:8px 0 4px;">Pruebas recientes</p>
                        <ul class="mkt-list">
                            @foreach ($pruebas as $p)
                                <li><span class="k">{{ $p->created_at->format('d/m H:i') }} · {{ $p->estadoLabel() }}</span><span class="v"><a href="{{ $p->enlace() }}" target="_blank" rel="noopener">Abrir enlace</a></span></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <div class="mkt-dlg-f">
                    <button type="button" class="erp-btn ghost" data-cerrar>Cerrar</button>
                    @if (! empty($numerosPrueba))<button type="submit" class="erp-btn">Mandar prueba</button>@endif
                </div>
            </form>
        </dialog>
    @endif
    @endcan
</div>

<script>
(function () {
    document.querySelectorAll('[data-abrir]').forEach(b => b.addEventListener('click', () => document.getElementById(b.dataset.abrir)?.showModal()));
    if (location.hash === '#enviar') document.getElementById('dlgEnviar')?.showModal();
    document.querySelectorAll('dialog [data-cerrar]').forEach(b => b.addEventListener('click', () => b.closest('dialog').close()));

    const fecha = document.getElementById('programada_para');
    const horario = document.getElementById('envHorario');
    const btn = document.getElementById('envBtn');
    const total = @json(number_format($nAud ?? 0));
    function cuando() {
        const prog = document.querySelector('input[name="cuando"]:checked')?.value === 'programar';
        document.getElementById('envFecha').hidden = !prog;
        if (fecha) fecha.required = prog;
        if (horario) horario.textContent = prog ? (fecha.value ? fecha.value.replace('T', ' ') : 'Elige fecha y hora') : 'Ahora mismo';
        if (btn) btn.textContent = prog ? 'Programar envío a ' + total + ' cliente(s)' : 'Enviar a ' + total + ' cliente(s)';
    }
    document.querySelectorAll('input[name="cuando"]').forEach(r => r.addEventListener('change', cuando));
    fecha?.addEventListener('input', cuando);
    if (btn) cuando();

    // Cliente de la prueba
    const inp = document.getElementById('pruebaCliente'), lista = document.getElementById('pruebaClienteLista');
    if (inp) {
        let t;
        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        inp.addEventListener('input', () => {
            document.getElementById('pruebaClienteId').value = '';
            clearTimeout(t);
            if (inp.value.trim().length < 2) { lista.classList.remove('on'); return; }
            t = setTimeout(async () => {
                const r = await fetch(@json(route('commercial.cotizaciones.clientes.buscar')) + '?q=' + encodeURIComponent(inp.value.trim()), { headers: { Accept: 'application/json' } });
                const datos = r.ok ? await r.json() : [];
                lista.innerHTML = '';
                datos.forEach(d => {
                    const div = document.createElement('div');
                    div.className = 'mkt-ac-item';
                    div.innerHTML = `<span>${esc(d.nombre)}</span><small>${esc(d.telefono || '')}</small>`;
                    div.addEventListener('mousedown', e => { e.preventDefault(); inp.value = d.nombre; document.getElementById('pruebaClienteId').value = d.id; lista.classList.remove('on'); });
                    lista.appendChild(div);
                });
                lista.classList.toggle('on', datos.length > 0);
            }, 220);
        });
    }
})();
</script>
@endsection
