@extends('layouts.dashboard')

@section('title', $r->folio)
@section('page-title', $r->folio)
@section('page-sub', $r->equipoNombre())

@section('content')
    @include('structure.garantias._estilos')

    @php
        $g = $r->garantia;
        $etapas = \App\Models\GarantiaReclamacion::ETAPAS;
        $claves = array_keys($etapas);
        $actual = array_search($r->etapa, $claves, true);
        $siguiente = $claves[$actual + 1] ?? null;
        if ($siguiente === 'atencion' && $r->dictamen === 'no_procede') $siguiente = 'entrega';
        $puedeGestionar = auth()->user()->can('garantias.gestionar');
        $abierta = $r->abierta();
        $costos = $r->costos;
    @endphp

    <x-ui.page-header :title="$r->folio.' · '.$r->equipoNombre()" :back="route('garantias.reclamaciones.index')">
        <span class="badge {{ $r->dictamenBadge() }}">{{ $r->dictamenLabel() }}</span>
        <x-ui.menu etiqueta="Documentos">
            <x-ui.menu-item :href="route('garantias.reclamaciones.documento', [$r, 'acuse'])" blank>Acuse de recibo (PDF)</x-ui.menu-item>
            <x-ui.menu-item :href="route('garantias.reclamaciones.documento', [$r, 'informe'])" blank>Informe técnico (PDF)</x-ui.menu-item>
            @unless ($abierta)
                <x-ui.menu-item :href="route('garantias.reclamaciones.documento', [$r, 'acta'])" blank>Acta de entrega (PDF)</x-ui.menu-item>
            @endunless
        </x-ui.menu>
    </x-ui.page-header>

    @if ($r->es_seguridad)
        <div class="gt-aviso rojo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <div><b>Posible incidente de seguridad</b>Evalúa y documenta conforme a tecnovigilancia (reporte al fabricante y, en su caso, a la autoridad sanitaria), independientemente de si la garantía procede.</div>
        </div>
    @endif
    @if ($r->atrasada())
        <div class="gt-aviso rojo"><div><b>Fuera de tiempo</b>
            {{ ! $r->primera_respuesta_en ? 'El cliente no ha recibido primera respuesta.' : 'Pasó su fecha límite ('.$r->limite_en->format('d/m/Y H:i').').' }}</div></div>
    @endif
    @if (! $g)
        <div class="gt-aviso">
            <div style="flex:1;"><b>Sin garantía ligada</b>Se registró con la serie «{{ $r->no_serie ?: 'sin serie' }}». Liga la garantía correcta para revisar la cobertura.</div>
            @if ($puedeGestionar)<button type="button" class="btn btn--ghost" data-abrir="dlgVincular">Ligar garantía</button>@endif
        </div>
    @endif

    {{-- ===================== Flujo ===================== --}}
    <div class="card gt-mb" style="padding:0;">
        <div class="gt-pasos">
            @foreach ($etapas as $clave => [$titulo])
                @php $i = $loop->index; @endphp
                <div class="gt-paso {{ $i < $actual || ! $abierta ? 'hecho' : ($i === $actual ? 'actual' : 'pendiente') }}" title="{{ $etapas[$clave][1] }}">
                    <span class="barra"></span>
                    <span class="n">Etapa {{ $i + 1 }}</span>
                    <span class="t">{{ $titulo }}</span>
                </div>
            @endforeach
        </div>

        @if ($abierta && $puedeGestionar)
            <div class="gt-acciones" style="justify-content:space-between; align-items:center;">
                <span class="gt-s">{{ $etapas[$r->etapa][1] }}</span>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    @if ($r->etapa === 'diagnostico' && ! $r->diagnostico)
                        <button type="button" class="btn btn--ghost" data-abrir="dlgDiagnostico">Capturar diagnóstico</button>
                    @endif
                    @if ($r->dictamen === 'pendiente' && in_array($r->etapa, ['revision', 'diagnostico'], true))
                        @can('garantias.dictaminar')
                            <button type="button" class="btn btn--ghost" data-abrir="dlgDictamen">Dictaminar</button>
                        @endcan
                    @endif
                    @if ($r->etapa === 'entrega' || ($r->dictamen !== 'pendiente' && $r->etapa === 'atencion'))
                        <button type="button" class="btn {{ $r->etapa === 'entrega' ? '' : 'btn--ghost' }}" data-abrir="dlgCerrar">Cerrar reclamación</button>
                    @endif
                    @if ($siguiente && $siguiente !== 'cerrada')
                        <form method="POST" action="{{ route('garantias.reclamaciones.etapa', $r) }}" style="margin:0;">
                            @csrf <input type="hidden" name="etapa" value="{{ $siguiente }}">
                            <button type="submit" class="btn">Pasar a: {{ $etapas[$siguiente][0] }}</button>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="gt-2">
        <div class="gt-col">
            {{-- Reporte --}}
            <div class="card" style="padding:0;">
                <div class="card-head"><h3>Reporte del cliente</h3><span class="right gt-s">{{ $r->canalLabel() }}</span></div>
                <div class="gt-txt">{{ $r->sintomas }}</div>
                <dl class="gt-kv" style="border-top:1px solid var(--border);">
                    <div><dt>Cliente</dt><dd>{{ $r->clienteNombre() }}</dd></div>
                    <div><dt>Reporta</dt><dd>{{ collect([$r->contacto_nombre, $r->contacto_telefono, $r->contacto_correo])->filter()->implode(' · ') ?: '—' }}</dd></div>
                    <div><dt>Serie</dt><dd class="gt-mono">@if ($r->no_serie)<a class="tbl-link" href="{{ route('garantias.serie', $r->no_serie) }}">{{ $r->no_serie }}</a>@else — @endif</dd></div>
                    <div><dt>Recibida</dt><dd>{{ $r->recibida_en->format('d/m/Y H:i') }}</dd></div>
                    <div><dt>Primera respuesta</dt><dd>{{ $r->primera_respuesta_en ? $r->primera_respuesta_en->format('d/m/Y H:i') : 'Pendiente' }}</dd></div>
                    <div><dt>Resolver antes de</dt><dd>{{ $r->limite_en?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                    @if ($r->cerrada_en)<div><dt>Cerrada</dt><dd>{{ $r->cerrada_en->format('d/m/Y H:i') }} · {{ $r->diasAbierta() }} día(s)</dd></div>@endif
                </dl>
            </div>

            {{-- Diagnóstico y dictamen --}}
            <div class="card" style="padding:0;">
                <div class="card-head">
                    <h3>Diagnóstico técnico</h3>
                    @if ($abierta && $puedeGestionar)
                        <span class="right"><button type="button" class="btn-icono" data-abrir="dlgDiagnostico" title="Editar diagnóstico" aria-label="Editar diagnóstico"><x-ui.icon name="edit" :size="16" /></button></span>
                    @endif
                </div>
                @if ($r->diagnostico || $r->pruebas || $r->causa)
                    @if ($r->diagnostico)<div class="gt-txt"><h4>Hallazgos</h4>{{ $r->diagnostico }}</div>@endif
                    @if ($r->pruebas)<div class="gt-txt"><h4>Pruebas y mediciones</h4>{{ $r->pruebas }}</div>@endif
                    @if ($r->causa)<div class="gt-txt"><h4>Causa identificada</h4>{{ $r->causa }}</div>@endif
                @else
                    <div class="gt-vacio">Servicio biomédico registra aquí fallas, mediciones, pruebas y la causa.</div>
                @endif
            </div>

            <div class="card" style="padding:0;">
                <div class="card-head">
                    <h3>Dictamen de cobertura</h3>
                    @if ($abierta)
                        @can('garantias.dictaminar')
                            <span class="right"><button type="button" class="btn btn--ghost" data-abrir="dlgDictamen" style="padding:6px 12px;">{{ $r->dictamen === 'pendiente' ? 'Dictaminar' : 'Cambiar' }}</button></span>
                        @endcan
                    @endif
                </div>
                @if ($r->dictamen === 'pendiente')
                    <div class="gt-vacio">Aún no se decide si la garantía procede.</div>
                @else
                    <div class="gt-txt" style="white-space:normal;">
                        <span class="badge {{ $r->dictamenBadge() }}">{{ $r->dictamenLabel() }}</span>
                        <span class="gt-s">· {{ $r->dictaminadaPor?->name }}, {{ $r->dictamen_en?->format('d/m/Y H:i') }}</span>
                    </div>
                    @if ($r->dictamen_motivo)<div class="gt-txt"><h4>Motivo</h4>{{ $r->dictamen_motivo }}</div>@endif
                    @if ($r->dictamen_fundamento)<div class="gt-txt"><h4>Fundamento</h4>{{ $r->dictamen_fundamento }}</div>@endif
                @endif
            </div>

            {{-- Costos --}}
            <div class="card" style="padding:0;">
                <div class="card-head">
                    <h3>Costos</h3>
                    <span class="right" style="display:flex; align-items:center; gap:10px;">
                        <span class="gt-s gt-num">${{ number_format($costos->where('estado', 'aprobado')->sum('monto'), 2) }}</span>
                        @if ($abierta && $puedeGestionar)<button type="button" class="btn btn--ghost" data-abrir="dlgCosto" style="padding:6px 12px;">Agregar gasto</button>@endif
                    </span>
                </div>
                @forelse ($costos as $c)
                    <div class="gt-fila">
                        <div>
                            <div class="t">{{ $c->conceptoLabel() }} · <span class="gt-num">${{ number_format($c->monto, 2) }}</span></div>
                            <div class="s">{{ $c->descripcion ? $c->descripcion.' · ' : '' }}Lo absorbe: {{ $c->absorbeLabel() }}
                                @if ($c->absorbe === 'fabricante') · recuperado ${{ number_format($c->recuperado, 2) }}@endif
                                · {{ $c->creadoPor?->name }}</div>
                        </div>
                        <div class="der">
                            <span class="badge {{ $c->estadoBadge() }}">{{ $c->estadoLabel() }}</span>
                            @if ($c->estado === 'pendiente')
                                @can('garantias.aprobar')
                                    <form method="POST" action="{{ route('garantias.reclamaciones.costo.resolver', $c) }}" style="margin:0;">@csrf<input type="hidden" name="aprobar" value="1"><button class="btn" style="padding:5px 10px;">Aprobar</button></form>
                                    <form method="POST" action="{{ route('garantias.reclamaciones.costo.resolver', $c) }}" style="margin:0;" data-confirm="El gasto queda rechazado y no cuenta en el costo de la garantía." data-confirm-title="Rechazar gasto">@csrf<input type="hidden" name="aprobar" value="0"><button class="btn btn--ghost" style="padding:5px 10px;">Rechazar</button></form>
                                @endcan
                            @elseif ($c->absorbe === 'fabricante' && $c->estado === 'aprobado' && $puedeGestionar)
                                <form method="POST" action="{{ route('garantias.reclamaciones.costo.recuperado', $c) }}" style="margin:0; display:flex; gap:6px;">
                                    @csrf
                                    <input type="number" step="0.01" min="0" max="{{ $c->monto }}" name="recuperado" value="{{ $c->recuperado }}" style="width:110px; padding:5px 8px;" aria-label="Monto recuperado">
                                    <button class="btn btn--ghost" style="padding:5px 10px;">Recuperado</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="gt-vacio">Refacciones, mano de obra, envíos… Más de ${{ number_format($limite, 0) }} requiere aprobación.</div>
                @endforelse
            </div>

            {{-- Préstamos y sustituciones --}}
            <div class="card" style="padding:0;">
                <div class="card-head">
                    <h3>Préstamos y sustituciones</h3>
                    @if ($abierta && $puedeGestionar)<span class="right"><button type="button" class="btn btn--ghost" data-abrir="dlgSustitucion" style="padding:6px 12px;">Registrar</button></span>@endif
                </div>
                @forelse ($r->sustituciones as $s)
                    <div class="gt-fila">
                        <div>
                            <div class="t">{{ $s->tipoLabel() }} · {{ $s->equipo }}</div>
                            <div class="s">
                                @if ($s->tipo === 'definitiva') Serie {{ $s->no_serie_anterior ?: '—' }} → {{ $s->no_serie_entregado }} ·
                                @else {{ $s->no_serie_entregado ? 'Serie '.$s->no_serie_entregado.' · ' : '' }} @endif
                                Entregado {{ $s->entregado_en->format('d/m/Y') }}
                                @if ($s->devolver_antes) · regresa antes del {{ $s->devolver_antes->format('d/m/Y') }}@endif
                            </div>
                        </div>
                        <div class="der">
                            @if ($s->pendienteDeDevolver())
                                <span class="badge {{ $s->devolver_antes && $s->devolver_antes->isPast() ? 'badge--danger' : 'badge--warn' }}">Con el cliente</span>
                                @if ($puedeGestionar)
                                    <form method="POST" action="{{ route('garantias.reclamaciones.sustitucion.devuelto', $s) }}" style="margin:0;">@csrf<button class="btn btn--ghost" style="padding:5px 10px;">Ya regresó</button></form>
                                @endif
                            @elseif ($s->tipo === 'prestamo')
                                <span class="badge badge--ok">Devuelto {{ $s->devuelto_en->format('d/m/Y') }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="gt-vacio">Equipo sustituto mientras se repara, o cambio definitivo de equipo o componente.</div>
                @endforelse
            </div>

            {{-- Evidencias --}}
            <div class="card" style="padding:0;">
                <div class="card-head">
                    <h3>Evidencias y documentos</h3>
                    @if ($puedeGestionar)<span class="right"><button type="button" class="btn btn--ghost" data-abrir="dlgArchivo" style="padding:6px 12px;">Adjuntar</button></span>@endif
                </div>
                @if ($r->archivos->isEmpty())
                    <div class="gt-vacio">Fotos o video de la falla, reporte técnico, actas firmadas…</div>
                @else
                    <div class="gt-archivos">
                        @foreach ($r->archivos as $a)
                            <a class="gt-arch" href="{{ $a->url() }}" target="_blank" rel="noopener" title="{{ $a->tipoLabel() }} · {{ $a->usuario?->name ?? 'Cliente' }}">
                                <span class="pv">@if ($a->esImagen())<img src="{{ $a->url() }}" alt="">@else{{ strtoupper(pathinfo($a->nombre, PATHINFO_EXTENSION)) }}@endif</span>
                                <span class="nm">{{ $a->nombre }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            @unless ($abierta)
                <div class="card" style="padding:0;">
                    <div class="card-head"><h3>Cierre</h3></div>
                    <dl class="gt-kv">
                        <div><dt>Resolución</dt><dd>{{ \App\Models\GarantiaReclamacion::RESOLUCIONES[$r->resolucion] ?? $r->resolucion }}</dd></div>
                        @if ($r->resolucion_detalle)<div><dt>Detalle</dt><dd>{{ $r->resolucion_detalle }}</dd></div>@endif
                        <div><dt>Recibió</dt><dd>{{ $r->entregado_a ?: '—' }}</dd></div>
                        <div><dt>Satisfacción</dt><dd>{{ $r->satisfaccion ? str_repeat('★', $r->satisfaccion).str_repeat('☆', 5 - $r->satisfaccion) : 'Sin calificar' }}</dd></div>
                        @if ($r->comentario_cliente)<div><dt>Comentario</dt><dd>{{ $r->comentario_cliente }}</dd></div>@endif
                    </dl>
                </div>
            @endunless
        </div>

        <div class="gt-col">
            {{-- Garantía --}}
            <div class="card" style="padding:0;">
                <div class="card-head"><h3>Garantía</h3>@if ($g)<span class="right"><a class="tbl-link" href="{{ route('garantias.show', $g) }}">Abrir expediente</a></span>@endif</div>
                @if ($g)
                    <dl class="gt-kv">
                        <div><dt>Folio</dt><dd>{{ $g->folio }}</dd></div>
                        <div><dt>Estado</dt><dd><span class="badge {{ $g->vigente() ? 'badge--ok' : 'badge--danger' }}">{{ $g->anulada() ? 'Anulada' : ($g->vigente() ? 'Vigente' : 'Vencida') }}</span></dd></div>
                        <div><dt>Tipo</dt><dd>{{ $g->tipoLabel() }}</dd></div>
                        <div><dt>Cobertura</dt><dd>{{ $g->inicio->format('d/m/Y') }} – {{ $g->vence->format('d/m/Y') }}</dd></div>
                        <div><dt>Fallas anteriores</dt><dd>{{ $g->reclamaciones->where('id', '!=', $r->id)->count() }}</dd></div>
                    </dl>
                    @if (! $g->vigente())
                        <p class="gt-s" style="margin:0; padding:0 18px 14px;">Fuera de cobertura: aceptarla es una excepción que autoriza quien tiene permiso de aprobar.</p>
                    @endif
                @else
                    <div class="gt-vacio">Sin garantía ligada.</div>
                @endif
            </div>

            {{-- Atención --}}
            @if ($puedeGestionar)
                <form method="POST" action="{{ route('garantias.reclamaciones.update', $r) }}" class="card" style="padding:0;">
                    @csrf @method('PUT')
                    <div class="card-head"><h3>Atención</h3></div>
                    <div class="gt-form">
                        <x-ui.form-group label="Responsable" for="u_resp">
                            <select id="u_resp" name="responsable_id">
                                <option value="">— Nadie —</option>
                                @foreach ($responsables as $u)<option value="{{ $u->id }}" @selected($r->responsable_id === $u->id)>{{ $u->name }}</option>@endforeach
                            </select>
                        </x-ui.form-group>
                        <x-ui.form-group label="Prioridad" for="u_prio">
                            <select id="u_prio" name="prioridad">
                                @foreach (\App\Models\GarantiaReclamacion::PRIORIDADES as $k => [$t])<option value="{{ $k }}" @selected($r->prioridad === $k)>{{ $t }}</option>@endforeach
                            </select>
                        </x-ui.form-group>
                        <label class="gt-check" style="margin-top:12px;"><input type="checkbox" name="es_seguridad" value="1" @checked($r->es_seguridad)> Posible incidente de seguridad</label>
                    </div>
                    <div class="gt-acciones"><button class="btn btn--ghost" type="submit">Guardar</button></div>
                </form>

                <form method="POST" action="{{ route('garantias.reclamaciones.servicio', $r) }}" class="card" style="padding:0;">
                    @csrf
                    <div class="card-head"><h3>Orden de servicio</h3>
                        @if ($r->service)<span class="right"><a class="tbl-link" href="{{ route('gestion.servicios.historial.show', $r->service) }}">Abrir</a></span>@endif</div>
                    <div class="gt-form">
                        <x-ui.form-group label="Reparación ligada" for="s_srv">
                            <select id="s_srv" name="service_id" onchange="this.form.submit()">
                                <option value="">— Ninguna —</option>
                                @if ($r->service && ! $servicios->contains('id', $r->service_id))
                                    <option value="{{ $r->service_id }}" selected>{{ $r->service->service_number }}</option>
                                @endif
                                @foreach ($servicios as $s)<option value="{{ $s->id }}" @selected($r->service_id === $s->id)>{{ $s->service_number }} · {{ $s->created_at?->format('d/m/Y') }}</option>@endforeach
                            </select>
                        </x-ui.form-group>
                        @can('servicios.crear')
                            <p class="campo-nota" style="margin:8px 0 0;">¿Aún no existe? <a class="tbl-link" href="{{ route('gestion.servicios.historial.nueva_orden') }}" target="_blank">Crear orden de servicio</a> y luego elígela aquí.</p>
                        @endcan
                    </div>
                </form>

                <form method="POST" action="{{ route('garantias.reclamaciones.nota', $r) }}" class="card" style="padding:0;">
                    @csrf
                    <div class="card-head"><h3>Seguimiento</h3></div>
                    <div class="gt-form">
                        <x-ui.form-group label="Nota" for="nota"><textarea id="nota" name="nota" rows="2" required placeholder="Llamada al cliente, refacción pedida, envío al fabricante…"></textarea></x-ui.form-group>
                    </div>
                    <div class="gt-acciones"><button class="btn btn--ghost" type="submit">Agregar a la bitácora</button></div>
                </form>
            @endif

            <div class="card" style="padding:0;">
                <div class="card-head"><h3>Bitácora</h3></div>
                <ul class="gt-bitacora">
                    @foreach ($r->eventos as $e)
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
    @if ($puedeGestionar)
        <dialog id="dlgDiagnostico" class="gt-modal">
            <form method="POST" action="{{ route('garantias.reclamaciones.diagnostico', $r) }}">
                @csrf
                <div class="gt-modal-head"><div><h2>Diagnóstico técnico</h2></div><button type="button" class="btn-icono" data-cerrar aria-label="Cerrar"><x-ui.icon name="x" :size="18" /></button></div>
                <div class="gt-modal-body">
                    <x-ui.form-group label="Hallazgos *" for="d_diag"><textarea id="d_diag" name="diagnostico" rows="4" required>{{ $r->diagnostico }}</textarea></x-ui.form-group>
                    <x-ui.form-group label="Pruebas y mediciones" for="d_pru"><textarea id="d_pru" name="pruebas" rows="3" placeholder="Pruebas de fuga, imagen, temperatura, seguridad eléctrica…">{{ $r->pruebas }}</textarea></x-ui.form-group>
                    <x-ui.form-group label="Causa identificada" for="d_cau"><textarea id="d_cau" name="causa" rows="2" placeholder="Defecto de fabricación, golpe, líquido, desgaste…">{{ $r->causa }}</textarea></x-ui.form-group>
                </div>
                <div class="gt-modal-pie"><button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button><button class="btn" type="submit">Guardar</button></div>
            </form>
        </dialog>

        <dialog id="dlgCosto" class="gt-modal">
            <form method="POST" action="{{ route('garantias.reclamaciones.costo', $r) }}">
                @csrf
                <div class="gt-modal-head"><div><h2>Agregar gasto</h2><p class="campo-nota" style="margin:0;">Arriba de ${{ number_format($limite, 2) }} queda esperando aprobación.</p></div><button type="button" class="btn-icono" data-cerrar aria-label="Cerrar"><x-ui.icon name="x" :size="18" /></button></div>
                <div class="gt-modal-body">
                    <div class="gt-form-grid">
                        <x-ui.form-group label="Concepto" for="c_con"><select id="c_con" name="concepto">@foreach (\App\Models\GarantiaCosto::CONCEPTOS as $k => $t)<option value="{{ $k }}">{{ $t }}</option>@endforeach</select></x-ui.form-group>
                        <x-ui.form-group label="Monto *" for="c_monto"><input type="number" id="c_monto" name="monto" step="0.01" min="0.01" required></x-ui.form-group>
                    </div>
                    <x-ui.form-group label="Lo absorbe" for="c_abs"><select id="c_abs" name="absorbe">@foreach (\App\Models\GarantiaCosto::ABSORBE as $k => $t)<option value="{{ $k }}">{{ $t }}</option>@endforeach</select></x-ui.form-group>
                    <x-ui.form-group label="Descripción" name="descripcion" placeholder="Refacción, número de parte, guía de envío…" />
                </div>
                <div class="gt-modal-pie"><button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button><button class="btn" type="submit">Agregar</button></div>
            </form>
        </dialog>

        <dialog id="dlgSustitucion" class="gt-modal">
            <form method="POST" action="{{ route('garantias.reclamaciones.sustitucion', $r) }}">
                @csrf
                <div class="gt-modal-head"><div><h2>Préstamo o sustitución</h2><p class="campo-nota" style="margin:0;">En una sustitución definitiva la serie nueva reemplaza a la anterior en la garantía.</p></div><button type="button" class="btn-icono" data-cerrar aria-label="Cerrar"><x-ui.icon name="x" :size="18" /></button></div>
                <div class="gt-modal-body">
                    <x-ui.form-group label="Tipo" for="s_tipo"><select id="s_tipo" name="tipo">@foreach (\App\Models\GarantiaSustitucion::TIPOS as $k => $t)<option value="{{ $k }}">{{ $t }}</option>@endforeach</select></x-ui.form-group>
                    <x-ui.form-group label="Equipo entregado *" name="equipo" required placeholder="Videogastroscopio de préstamo" />
                    <div class="gt-form-grid">
                        <x-ui.form-group label="Serie del equipo entregado" name="no_serie_entregado" />
                        <x-ui.form-group label="Fecha de entrega" for="s_ent"><input type="date" id="s_ent" name="entregado_en" value="{{ today()->toDateString() }}" required></x-ui.form-group>
                        <x-ui.form-group label="Debe regresar antes de" for="s_dev"><input type="date" id="s_dev" name="devolver_antes"></x-ui.form-group>
                    </div>
                    <x-ui.form-group label="Condiciones" for="s_cond"><textarea id="s_cond" name="condiciones" rows="2" placeholder="Estado en que se entrega, accesorios, responsabilidad del cliente…"></textarea></x-ui.form-group>
                </div>
                <div class="gt-modal-pie"><button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button><button class="btn" type="submit">Registrar</button></div>
            </form>
        </dialog>

        <dialog id="dlgArchivo" class="gt-modal">
            <form method="POST" action="{{ route('garantias.reclamaciones.archivo', $r) }}" enctype="multipart/form-data">
                @csrf
                <div class="gt-modal-head"><div><h2>Adjuntar</h2></div><button type="button" class="btn-icono" data-cerrar aria-label="Cerrar"><x-ui.icon name="x" :size="18" /></button></div>
                <div class="gt-modal-body">
                    <x-ui.form-group label="Tipo" for="ar_tipo"><select id="ar_tipo" name="tipo">@foreach (\App\Models\GarantiaArchivo::TIPOS as $k => $t)<option value="{{ $k }}">{{ $t }}</option>@endforeach</select></x-ui.form-group>
                    <x-ui.form-group label="Archivos" for="ar_files"><input type="file" id="ar_files" name="archivos[]" multiple required></x-ui.form-group>
                </div>
                <div class="gt-modal-pie"><button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button><button class="btn" type="submit">Subir</button></div>
            </form>
        </dialog>

        <dialog id="dlgCerrar" class="gt-modal">
            <form method="POST" action="{{ route('garantias.reclamaciones.cerrar', $r) }}">
                @csrf
                <div class="gt-modal-head"><div><h2>Cerrar reclamación</h2><p class="campo-nota" style="margin:0;">Después podrás descargar el acta de entrega.</p></div><button type="button" class="btn-icono" data-cerrar aria-label="Cerrar"><x-ui.icon name="x" :size="18" /></button></div>
                <div class="gt-modal-body">
                    <x-ui.form-group label="Resolución *" for="z_res"><select id="z_res" name="resolucion">@foreach (\App\Models\GarantiaReclamacion::RESOLUCIONES as $k => $t)<option value="{{ $k }}">{{ $t }}</option>@endforeach</select></x-ui.form-group>
                    <x-ui.form-group label="Detalle" for="z_det"><textarea id="z_det" name="resolucion_detalle" rows="3"></textarea></x-ui.form-group>
                    <div class="gt-form-grid">
                        <x-ui.form-group label="Recibió el equipo" name="entregado_a" placeholder="Nombre de quien recibe" />
                        <x-ui.form-group label="Satisfacción del cliente" for="z_sat">
                            <select id="z_sat" name="satisfaccion"><option value="">Sin calificar</option>@for ($i = 5; $i >= 1; $i--)<option value="{{ $i }}">{{ str_repeat('★', $i) }} ({{ $i }})</option>@endfor</select>
                        </x-ui.form-group>
                    </div>
                    <x-ui.form-group label="Comentario del cliente" for="z_com"><textarea id="z_com" name="comentario_cliente" rows="2"></textarea></x-ui.form-group>
                </div>
                <div class="gt-modal-pie"><button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button><button class="btn" type="submit">Cerrar reclamación</button></div>
            </form>
        </dialog>

        <dialog id="dlgVincular" class="gt-modal">
            <form method="POST" action="{{ route('garantias.reclamaciones.vincular', $r) }}">
                @csrf
                <div class="gt-modal-head"><div><h2>Ligar a una garantía</h2></div><button type="button" class="btn-icono" data-cerrar aria-label="Cerrar"><x-ui.icon name="x" :size="18" /></button></div>
                <div class="gt-modal-body">
                    <x-ui.form-group label="Buscar por serie, folio, equipo o cliente" for="v_q"><input type="text" id="v_q" autocomplete="off" value="{{ $r->no_serie }}"></x-ui.form-group>
                    <div id="v_res" style="margin-top:10px;"></div>
                </div>
                <div class="gt-modal-pie"><button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button><button class="btn" type="submit">Ligar</button></div>
            </form>
        </dialog>
    @endif

    @can('garantias.dictaminar')
        <dialog id="dlgDictamen" class="gt-modal">
            <form method="POST" action="{{ route('garantias.reclamaciones.dictamen', $r) }}">
                @csrf
                <div class="gt-modal-head"><div><h2>Dictamen de cobertura</h2><p class="campo-nota" style="margin:0;">Negar la garantía exige motivo, fundamento y al menos una evidencia adjunta.</p></div><button type="button" class="btn-icono" data-cerrar aria-label="Cerrar"><x-ui.icon name="x" :size="18" /></button></div>
                <div class="gt-modal-body">
                    <x-ui.form-group label="Dictamen *" for="x_dic">
                        <select id="x_dic" name="dictamen">
                            @foreach (['procede', 'parcial', 'no_procede'] as $k)<option value="{{ $k }}" @selected($r->dictamen === $k)>{{ \App\Models\GarantiaReclamacion::DICTAMENES[$k][0] }}</option>@endforeach
                        </select>
                    </x-ui.form-group>
                    @if ($g && ! $g->vigente())
                        <div class="gt-aviso" style="margin-top:12px;"><div>La garantía no está vigente. Aceptarla requiere permiso de aprobar excepciones.</div></div>
                    @endif
                    <x-ui.form-group label="Motivo" for="x_mot"><textarea id="x_mot" name="motivo" rows="3">{{ $r->dictamen_motivo }}</textarea></x-ui.form-group>
                    <x-ui.form-group label="Fundamento (cláusula de la póliza o disposición aplicable)" for="x_fun"><textarea id="x_fun" name="fundamento" rows="3" placeholder="Ej. Exclusiones de la póliza {{ $g?->folio }}: daño por líquidos.">{{ $r->dictamen_fundamento }}</textarea></x-ui.form-group>
                </div>
                <div class="gt-modal-pie"><button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button><button class="btn" type="submit">Registrar dictamen</button></div>
            </form>
        </dialog>
    @endcan

    @if ($puedeGestionar && ! $g)
        @push('scripts')
        <script>
        (function () {
            var q = document.getElementById('v_q'), caja = document.getElementById('v_res'), t;
            function buscar() {
                var texto = q.value.trim();
                if (texto.length < 2) { caja.innerHTML = ''; return; }
                fetch('{{ route('garantias.reclamaciones.buscar-serie') }}?q=' + encodeURIComponent(texto), { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (lista) {
                        caja.innerHTML = lista.length ? '' : '<p class="gt-s">Sin resultados.</p>';
                        lista.forEach(function (g, i) {
                            var l = document.createElement('label');
                            l.className = 'gt-check';
                            l.style.padding = '8px 0';
                            l.innerHTML = '<input type="radio" name="garantia_id" required><span><b></b><br><span class="gt-s"></span></span>';
                            l.querySelector('input').value = g.id;
                            if (i === 0) l.querySelector('input').checked = true;
                            l.querySelector('b').textContent = g.folio + ' · ' + g.equipo;
                            l.querySelector('.gt-s').textContent = g.cliente + ' · serie ' + (g.serie || '—') + ' · ' + g.semaforo;
                            caja.appendChild(l);
                        });
                    });
            }
            q.addEventListener('input', function () { clearTimeout(t); t = setTimeout(buscar, 250); });
            buscar();
        })();
        </script>
        @endpush
    @endif
@endsection
