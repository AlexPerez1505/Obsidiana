@extends('layouts.dashboard')

@section('title', $g->folio)
@section('page-title', $g->folio)
@section('page-sub', $g->clienteNombre())

@section('content')
    @include('structure.garantias._estilos')

    @php
        $semaforo = $g->semaforo();
        $dias = $g->diasRestantes();
        $r = $rentabilidad;
        $dinero = fn ($v) => $v === null ? '—' : ($v < 0 ? '−$' : '$').number_format(abs($v), 2);
    @endphp

    <x-ui.page-header :title="$g->equipoNombre()" :back="route('garantias.index')">
        <span class="badge {{ $g->semaforoBadge() }}">{{ $g->semaforoTexto() }}</span>
        <a href="{{ route('garantias.poliza', $g) }}" target="_blank" class="btn btn--ghost">Póliza PDF</a>
        @can('garantias.gestionar')
            @unless ($g->anulada())
                <a href="{{ route('garantias.reclamaciones.create', ['garantia' => $g->id]) }}" class="btn">Registrar reclamación</a>
            @endunless
        @endcan
    </x-ui.page-header>

    @if ($g->anulada())
        <div class="gt-aviso rojo">
            <div><b>Garantía anulada</b>{{ $g->motivo_anulacion }}
                <span class="gt-s">· {{ $g->anuladaPor?->name ?? 'Sistema' }}, {{ $g->anulada_en?->format('d/m/Y H:i') }}</span></div>
        </div>
    @elseif (! $g->fecha_entrega && $g->origen === 'venta')
        <div class="gt-aviso">
            <div><b>Entrega por confirmar</b>La cobertura se cuenta provisionalmente desde la fecha de venta. Cuando se firme la entrega, las fechas se ajustan solas.</div>
        </div>
    @endif

    <div class="gt-2">
        <div class="gt-col">
            <div class="card" style="padding:0;">
                <div class="card-head">
                    <h3>Expediente</h3>
                    @can('garantias.gestionar')
                        <span class="right"><button type="button" class="btn-icono" data-abrir="dlgEditar" title="Editar datos" aria-label="Editar datos"><x-ui.icon name="edit" :size="16" /></button></span>
                    @endcan
                </div>
                <dl class="gt-kv">
                    <div><dt>Folio</dt><dd>{{ $g->folio }}</dd></div>
                    <div><dt>Cliente</dt><dd>{{ $g->clienteNombre() }}</dd></div>
                    <div><dt>Venta</dt><dd>
                        @if ($g->venta)
                            <a class="tbl-link" href="{{ route('commercial.ventas.show', $g->venta) }}">{{ $g->venta->folio }}</a>
                        @else Alta manual @endif
                    </dd></div>
                    <div><dt>Número de serie</dt><dd class="gt-mono">
                        @if ($g->no_serie)
                            <a class="tbl-link" href="{{ route('garantias.serie', $g->no_serie) }}">{{ $g->no_serie }}</a>
                        @else <span class="gt-muted">Sin registrar</span> @endif
                    </dd></div>
                    @if ($g->codigo_interno)
                        <div><dt>Etiqueta interna</dt><dd class="gt-mono">{{ $g->codigo_interno }}</dd></div>
                    @endif
                    <div><dt>Condición</dt><dd>{{ $g->condicionLabel() }}</dd></div>
                    <div><dt>Tipo de garantía</dt><dd>{{ $g->tipoLabel() }}</dd></div>
                    <div><dt>Fecha de venta</dt><dd>{{ $g->fecha_venta?->format('d/m/Y') ?? '—' }}</dd></div>
                    <div><dt>Fecha de entrega</dt><dd>{{ $g->fecha_entrega?->format('d/m/Y') ?? 'Por confirmar' }}</dd></div>
                    <div><dt>Cobertura</dt><dd>{{ $g->inicio->format('d/m/Y') }} – {{ $g->vence->format('d/m/Y') }} · {{ $g->meses }} meses
                        @if ($g->fechas_manual)<div class="gt-s">Fechas fijadas a mano</div>@endif</dd></div>
                    <div><dt>{{ $dias >= 0 ? 'Le quedan' : 'Venció hace' }}</dt><dd>{{ abs($dias) }} día(s)</dd></div>
                    <div><dt>Técnico responsable</dt><dd>{{ $g->responsable?->name ?? '—' }}</dd></div>
                    <div><dt>Proveedor</dt><dd>{{ $g->proveedor ?: '—' }}</dd></div>
                    @if ($g->notas)<div><dt>Notas</dt><dd>{{ $g->notas }}</dd></div>@endif
                </dl>
            </div>

            @if ($hermanos->isNotEmpty())
                <div class="card" style="padding:0;">
                    <div class="card-head"><h3>{{ $g->grupo }}</h3><span class="right gt-s">Cada componente con su propia garantía</span></div>
                    <div class="gt-torre">
                        @foreach (collect([$g])->merge($hermanos) as $c)
                            <a class="gt-comp {{ $c->id === $g->id ? 'yo' : '' }}" href="{{ route('garantias.show', $c) }}">
                                <div class="t">{{ $c->equipoNombre() }}</div>
                                <div class="s">{{ $c->no_serie ?: 'Sin serie' }} · {{ $c->meses }} meses</div>
                                <span class="badge {{ $c->semaforoBadge() }}">{{ $c->semaforoTexto() }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="card" style="padding:0;">
                <div class="card-head"><h3>Reclamaciones</h3><span class="right gt-s">{{ $g->reclamaciones->count() }}</span></div>
                @forelse ($g->reclamaciones as $rec)
                    <a class="gt-fila" href="{{ route('garantias.reclamaciones.show', $rec) }}">
                        <div>
                            <div class="t">{{ $rec->folio }} · {{ $rec->recibida_en->format('d/m/Y') }}</div>
                            <div class="s">{{ mb_strimwidth($rec->sintomas, 0, 120, '…') }}</div>
                        </div>
                        <div class="der">
                            @if ($rec->costoTotal() > 0)<span class="gt-s gt-num">${{ number_format($rec->costoTotal(), 0) }}</span>@endif
                            <span class="badge {{ $rec->dictamenBadge() }}">{{ $rec->dictamenLabel() }}</span>
                            <span class="badge {{ $rec->abierta() ? 'badge--info' : '' }}">{{ $rec->etapaLabel() }}</span>
                        </div>
                    </a>
                @empty
                    <div class="gt-vacio">Este equipo no ha presentado fallas.</div>
                @endforelse
            </div>

            <div class="card" style="padding:0;">
                <div class="card-head"><h3>Condiciones de la póliza</h3></div>
                <div class="gt-txt"><h4>Qué cubre</h4>{{ $g->cubre ?: 'Sin especificar.' }}</div>
                <div class="gt-txt"><h4>Exclusiones</h4>{{ $g->exclusiones ?: 'Sin especificar.' }}</div>
                @if ($g->condiciones)<div class="gt-txt"><h4>Condiciones</h4>{{ $g->condiciones }}</div>@endif
            </div>

            <div class="card" style="padding:0;">
                <div class="card-head">
                    <h3>Documentos del expediente</h3>
                    @can('garantias.gestionar')
                        <span class="right"><button type="button" class="btn btn--ghost" data-abrir="dlgArchivo" style="padding:6px 12px;">Adjuntar</button></span>
                    @endcan
                </div>
                @if ($g->archivos->isEmpty())
                    <div class="gt-vacio">Factura, acta de entrega, certificado del fabricante…</div>
                @else
                    <div class="gt-archivos">
                        @foreach ($g->archivos as $a)
                            <a class="gt-arch" href="{{ $a->url() }}" target="_blank" rel="noopener" title="{{ $a->tipoLabel() }} · {{ $a->usuario?->name }}">
                                <span class="pv">@if ($a->esImagen())<img src="{{ $a->url() }}" alt="">@else{{ strtoupper(pathinfo($a->nombre, PATHINFO_EXTENSION)) }}@endif</span>
                                <span class="nm">{{ $a->nombre }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="gt-col">
            <div class="card" style="padding:0;">
                <div class="card-head"><h3>Rentabilidad del equipo</h3></div>
                <dl class="gt-kv">
                    <div><dt>Venta</dt><dd class="gt-num">{{ $dinero($r['precio']) }}</dd></div>
                    <div><dt>Costo de adquisición</dt><dd class="gt-num">{{ $dinero($r['costo']) }}</dd></div>
                    @foreach ($r['por_concepto'] as $concepto => $monto)
                        <div><dt>{{ \App\Models\GarantiaCosto::CONCEPTOS[$concepto] ?? $concepto }}</dt><dd class="gt-num">−${{ number_format($monto, 2) }}</dd></div>
                    @endforeach
                    @if ($r['recuperado'] > 0)<div><dt>Recuperado del fabricante</dt><dd class="gt-num" style="color:var(--green);">+${{ number_format($r['recuperado'], 2) }}</dd></div>@endif
                    @if ($r['del_cliente'] > 0)<div><dt>Pagado por el cliente</dt><dd class="gt-num">+${{ number_format($r['del_cliente'], 2) }}</dd></div>@endif
                    <div><dt><b>Margen después de garantía</b></dt><dd class="gt-num"><b>{{ $dinero($r['margen']) }}</b></dd></div>
                </dl>
                @if ($r['costo'] === null)
                    <p class="gt-s" style="margin:0; padding:0 18px 14px;">Captura el costo de adquisición para ver el margen real.</p>
                @endif
            </div>

            <div class="card" style="padding:0;">
                <div class="card-head"><h3>Portal del cliente</h3></div>
                <div class="gt-txt" style="white-space:normal;">
                    El cliente consulta su cobertura, descarga la póliza y reporta fallas desde este enlace (también va como QR en la póliza).
                    <div style="display:flex; gap:8px; margin-top:10px;">
                        <input type="text" readonly value="{{ $g->urlPublica() }}" id="gtEnlace" style="flex:1; font-size:12.5px;">
                        <button type="button" class="btn btn--ghost" onclick="navigator.clipboard.writeText(document.getElementById('gtEnlace').value).then(function(){ window.showToast && showToast('Enlace copiado', 'ok'); })">Copiar</button>
                    </div>
                </div>
            </div>

            @can('garantias.aprobar')
                <div class="card" style="padding:0;">
                    <div class="card-head"><h3>Autorizaciones</h3></div>
                    <div class="gt-txt" style="white-space:normal; display:flex; flex-direction:column; gap:8px;">
                        <button type="button" class="btn btn--ghost" data-abrir="dlgFechas">Corregir cobertura o fechas</button>
                        @if ($g->anulada())
                            <form method="POST" action="{{ route('garantias.reactivar', $g) }}" data-confirm="La garantía vuelve a estar activa." data-confirm-title="Reactivar garantía">
                                @csrf <button type="submit" class="btn btn--ghost" style="width:100%;">Reactivar garantía</button>
                            </form>
                        @else
                            <button type="button" class="btn btn--ghost" data-abrir="dlgAnular" style="color:var(--danger);">Anular garantía</button>
                        @endif
                        <span class="gt-s">Todo cambio queda en la bitácora con quién lo hizo y por qué.</span>
                    </div>
                </div>
            @endcan

            <div class="card" style="padding:0;">
                <div class="card-head"><h3>Bitácora</h3></div>
                <ul class="gt-bitacora">
                    @foreach ($g->eventos as $e)
                        <li class="e-{{ $e->tipo }}">
                            <div class="d">{{ $e->descripcion }}</div>
                            <div class="m">{{ $e->autor() }} · {{ $e->created_at->format('d/m/Y H:i') }}</div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    {{-- ===================== Ventanas ===================== --}}
    @can('garantias.gestionar')
        <dialog id="dlgEditar" class="gt-modal">
            <form method="POST" action="{{ route('garantias.update', $g) }}">
                @csrf @method('PUT')
                <div class="gt-modal-head"><div><h2>Editar expediente</h2><p class="campo-nota" style="margin:0;">Las fechas de cobertura se corrigen aparte, con autorización.</p></div>
                    <button type="button" class="btn-icono" data-cerrar aria-label="Cerrar"><x-ui.icon name="x" :size="18" /></button></div>
                <div class="gt-modal-body">
                    <div class="gt-form-grid">
                        <x-ui.form-group label="Número de serie" name="no_serie" :value="$g->no_serie" maxlength="120" />
                        <x-ui.form-group label="Condición" for="e_condicion">
                            <select id="e_condicion" name="condicion">
                                @foreach (\App\Models\Garantia::CONDICIONES as $k => $t)<option value="{{ $k }}" @selected($g->condicion === $k)>{{ $t }}</option>@endforeach
                            </select>
                        </x-ui.form-group>
                        <x-ui.form-group label="Técnico responsable" for="e_resp">
                            <select id="e_resp" name="responsable_id">
                                <option value="">— Nadie —</option>
                                @foreach ($responsables as $u)<option value="{{ $u->id }}" @selected($g->responsable_id === $u->id)>{{ $u->name }}</option>@endforeach
                            </select>
                        </x-ui.form-group>
                        <x-ui.form-group label="Proveedor" name="proveedor" :value="$g->proveedor" maxlength="160" />
                        <x-ui.form-group label="Precio de venta" for="e_precio">
                            <input type="number" step="0.01" min="0" id="e_precio" name="precio_venta" value="{{ $g->precio_venta }}">
                        </x-ui.form-group>
                        <x-ui.form-group label="Costo de adquisición" for="e_costo">
                            <input type="number" step="0.01" min="0" id="e_costo" name="costo_adquisicion" value="{{ $g->costo_adquisicion }}">
                        </x-ui.form-group>
                    </div>
                    <x-ui.form-group label="Qué cubre" for="e_cubre"><textarea id="e_cubre" name="cubre" rows="3">{{ $g->cubre }}</textarea></x-ui.form-group>
                    <x-ui.form-group label="Exclusiones" for="e_excl"><textarea id="e_excl" name="exclusiones" rows="3">{{ $g->exclusiones }}</textarea></x-ui.form-group>
                    <x-ui.form-group label="Condiciones adicionales" for="e_cond"><textarea id="e_cond" name="condiciones" rows="2">{{ $g->condiciones }}</textarea></x-ui.form-group>
                    <x-ui.form-group label="Notas internas" for="e_notas"><textarea id="e_notas" name="notas" rows="2">{{ $g->notas }}</textarea></x-ui.form-group>
                </div>
                <div class="gt-modal-pie"><button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button><button type="submit" class="btn">Guardar</button></div>
            </form>
        </dialog>

        <dialog id="dlgArchivo" class="gt-modal">
            <form method="POST" action="{{ route('garantias.archivo', $g) }}" enctype="multipart/form-data">
                @csrf
                <div class="gt-modal-head"><div><h2>Adjuntar al expediente</h2></div>
                    <button type="button" class="btn-icono" data-cerrar aria-label="Cerrar"><x-ui.icon name="x" :size="18" /></button></div>
                <div class="gt-modal-body">
                    <x-ui.form-group label="Tipo de documento" for="a_tipo">
                        <select id="a_tipo" name="tipo">
                            @foreach (\App\Models\GarantiaArchivo::TIPOS as $k => $t)<option value="{{ $k }}" @selected($k === 'factura')>{{ $t }}</option>@endforeach
                        </select>
                    </x-ui.form-group>
                    <x-ui.form-group label="Archivos (fotos, PDF, video)" for="a_archivos">
                        <input type="file" id="a_archivos" name="archivos[]" multiple required>
                    </x-ui.form-group>
                </div>
                <div class="gt-modal-pie"><button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button><button type="submit" class="btn">Subir</button></div>
            </form>
        </dialog>
    @endcan

    @can('garantias.aprobar')
        <dialog id="dlgFechas" class="gt-modal">
            <form method="POST" action="{{ route('garantias.fechas', $g) }}">
                @csrf
                <div class="gt-modal-head"><div><h2>Corregir cobertura</h2><p class="campo-nota" style="margin:0;">Queda fijada a mano: ya no se recalcula con la entrega.</p></div>
                    <button type="button" class="btn-icono" data-cerrar aria-label="Cerrar"><x-ui.icon name="x" :size="18" /></button></div>
                <div class="gt-modal-body">
                    <div class="gt-form-grid">
                        <x-ui.form-group label="Inicio de cobertura" for="f_inicio"><input type="date" id="f_inicio" name="inicio" value="{{ $g->inicio->toDateString() }}" required></x-ui.form-group>
                        <x-ui.form-group label="Meses" for="f_meses"><input type="number" id="f_meses" name="meses" min="1" max="120" value="{{ $g->meses }}" required></x-ui.form-group>
                    </div>
                    <x-ui.form-group label="Tipo de garantía" for="f_tipo">
                        <select id="f_tipo" name="tipo">
                            @foreach (\App\Models\Garantia::TIPOS as $k => $t)<option value="{{ $k }}" @selected($g->tipo === $k)>{{ $t }}</option>@endforeach
                        </select>
                    </x-ui.form-group>
                    <x-ui.form-group label="Motivo del cambio *" for="f_motivo"><textarea id="f_motivo" name="motivo" rows="2" required minlength="5" placeholder="Ej. garantía extendida contratada, error en la fecha de entrega…"></textarea></x-ui.form-group>
                </div>
                <div class="gt-modal-pie"><button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button><button type="submit" class="btn">Guardar cambio</button></div>
            </form>
        </dialog>

        <dialog id="dlgAnular" class="gt-modal">
            <form method="POST" action="{{ route('garantias.anular', $g) }}">
                @csrf
                <div class="gt-modal-head"><div><h2>Anular garantía</h2><p class="campo-nota" style="margin:0;">No se borra: queda en el historial con el motivo.</p></div>
                    <button type="button" class="btn-icono" data-cerrar aria-label="Cerrar"><x-ui.icon name="x" :size="18" /></button></div>
                <div class="gt-modal-body">
                    <x-ui.form-group label="Motivo *" for="n_motivo"><textarea id="n_motivo" name="motivo" rows="3" required minlength="5"></textarea></x-ui.form-group>
                </div>
                <div class="gt-modal-pie"><button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button><button type="submit" class="btn btn--danger">Anular</button></div>
            </form>
        </dialog>
    @endcan
@endsection
