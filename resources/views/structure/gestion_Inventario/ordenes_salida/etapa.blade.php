@extends('layouts.dashboard')

@section('title', $etapa->nombre() . ' · ' . $orden->folio)
@section('page-title', $etapa->nombre() . ' · ' . $orden->folio)
@section('page-sub', 'Gestión de Inventario > Órdenes de salida > ' . $orden->folio . ' > Etapa ' . $etapa->numero())

@php
    use App\Models\OrdenSalidaEtapa as E;

    $venta = $orden->venta;
    $cliente = trim(($venta?->customer?->nombre ?? '').' '.($venta?->customer?->apellido ?? '')) ?: 'Sin cliente';
    $corrigiendo = $etapa->terminada() && $puedeEditar;
    $tono = ['pendiente' => '', 'en_curso' => 'badge--info', 'terminada' => 'badge--ok'];
    $yo = auth()->user();
    $usaRegistrada = $yo->tieneFirma();
    $medios = $evidencias->map(fn ($ev) => ['tipo' => $ev->esVideo() ? 'video' : 'foto', 'url' => $ev->url()])->values();
    $extrasTitulo = match ($etapa->etapa) {
        E::INGENIERIA => '¿Qué agrega de más?',
        E::EMPAQUE => 'Piezas con las que sale',
        default => null,
    };
    $extrasNota = match ($etapa->etapa) {
        E::INGENIERIA => 'Accesorios, refacciones, cables o lo que se agregue al equipo además de lo vendido.',
        E::EMPAQUE => 'Marca cada pieza que va en el paquete. Puedes agregar las que falten.',
        default => null,
    };
    $datos = $etapa->datos ?: [];
@endphp

@section('content')
    @if (session('status'))
        <x-ui.alert type="ok" style="margin-bottom:14px;">{{ session('status') }}</x-ui.alert>
    @endif
    @if ($errors->any())
        <x-ui.alert type="err" style="margin-bottom:14px;">
            <b>Para continuar:</b>
            <ul style="margin:4px 0 0; padding-left:18px;">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </x-ui.alert>
    @endif

    {{-- ===================== Encabezado: las tres etapas ===================== --}}
    <x-ui.card class="et-cab">
        <div class="et-top">
            <div class="et-id">
                <span class="badge {{ $tono[$etapa->estado] ?? '' }}">{{ $etapa->estadoLabel() }}</span>
                <span class="muted">{{ $orden->folio }} · {{ $venta?->folio }} · {{ $cliente }}</span>
            </div>
            <div class="et-acc">
                <a href="{{ route('salidas.seguimiento', $orden) }}" class="btn btn--ghost">Ver en qué va</a>
                <a href="{{ route('salidas.expediente', $orden) }}" target="_blank" class="btn btn--ghost">PDF del expediente</a>
                <a href="{{ route('inventory.salidas.show', $orden) }}" class="btn-icono" title="Regresar a la orden" aria-label="Regresar a la orden">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                </a>
            </div>
        </div>

        <nav class="et-pasos" aria-label="Etapas de la salida">
            @foreach ($etapas as $e)
                <a href="{{ route('inventory.salidas.etapas.show', [$orden, $e->etapa]) }}"
                   class="et-paso is-{{ $e->estado }} {{ $e->etapa === $etapa->etapa ? 'is-actual' : '' }}">
                    <span class="et-num">{!! $e->terminada() ? '&#10003;' : $e->numero() !!}</span>
                    <span class="et-txt">
                        <b>{{ $e->nombre() }}</b>
                        <small>{{ $e->asignadaA?->name ?? 'Sin responsable' }}</small>
                    </span>
                </a>
            @endforeach
        </nav>

        <div class="et-resp">
            <div>
                <div class="et-k">Responsable de {{ $etapa->nombre() }}</div>
                <div class="et-v">{{ $etapa->asignadaA?->name ?? 'Nadie asignado' }}
                    @if ($etapa->asignada_en)<small class="muted"> · asignó {{ $etapa->asignadaPor?->name ?? '—' }} el {{ $etapa->asignada_en->format('d/m/Y H:i') }}</small>@endif
                </div>
                <p class="muted et-desc">{{ E::DESCRIPCION[$etapa->etapa] }}</p>
            </div>
            @if ($puedeAsignar && ! $orden->cerrada() && ! $etapa->terminada())
                <form method="POST" action="{{ route('inventory.salidas.etapas.asignar', $orden) }}" class="et-asig">
                    @csrf
                    <select name="etapas[{{ $etapa->etapa }}]" data-no-mayus aria-label="Responsable de {{ $etapa->nombre() }}">
                        <option value="">— Sin responsable —</option>
                        @foreach ($candidatos as $c)
                            <option value="{{ $c->id }}" @selected((int) $etapa->asignada_a === $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn--ghost">{{ $etapa->asignada_a ? 'Cambiar' : 'Asignar' }}</button>
                </form>
            @endif
        </div>
    </x-ui.card>

    {{-- ===================== Etapa terminada: resumen y corregir ===================== --}}
    @if ($etapa->terminada() && ! $corrigiendo)
        <x-ui.card class="et-hecha">
            <div class="et-hecha-top">
                <div>
                    <div class="et-sello">Terminada</div>
                    <p style="margin:6px 0 0;">
                        La terminó <b>{{ $etapa->terminadaPor?->name ?? '—' }}</b> el {{ $etapa->terminada_en?->format('d/m/Y H:i') }}.
                        @if ($etapa->editada_en)<span class="muted">Corregida por {{ $etapa->editadaPor?->name }} el {{ $etapa->editada_en->format('d/m/Y H:i') }}.</span>@endif
                    </p>
                </div>
                @if ($puedeCorregir)
                    @if ($etapa->enPlazoDeEdicion())
                        <form method="POST" action="{{ route('inventory.salidas.etapas.desbloquear', [$orden, $etapa->etapa]) }}">
                            @csrf
                            <button type="submit" class="btn">Editar</button>
                            <div class="muted et-plazo">Sin NIP por {{ $etapa->horasParaEditar() }} h más</div>
                        </form>
                    @else
                        <button type="button" class="btn" data-pedir-nip>Editar con NIP</button>
                    @endif
                @endif
            </div>
        </x-ui.card>
    @endif

    @if ($corrigiendo)
        <x-ui.alert style="margin-bottom:14px;">
            Estás corrigiendo una etapa terminada. Guarda los cambios antes de {{ \App\Services\Almacen\EtapasDeSalida::MINUTOS_DESBLOQUEO }} minutos; se avisará al asesor y a los administradores.
        </x-ui.alert>
    @endif

    @if (! $puedeEditar && ! $etapa->terminada() && $razon)
        <x-ui.alert style="margin-bottom:14px;">{{ $razon }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('inventory.salidas.etapas.guardar', [$orden, $etapa->etapa]) }}" id="etForm" data-live="off"
          class="et-cuerpo {{ $puedeEditar ? '' : 'is-lectura' }}">
        @csrf
        <input type="hidden" name="accion" value="guardar" id="etAccion">

        <div class="et-principal">
            {{-- ===================== Checklist ===================== --}}
            <x-ui.card>
                <div class="et-sec-cab">
                    <x-ui.section-title style="margin:0;">Checklist de {{ mb_strtolower($etapa->nombre()) }}</x-ui.section-title>
                    @if ($etapa->checklist)
                        <span class="muted et-mini">{{ $etapa->checklist_ia ? 'Hecho con IA para este equipo' : 'Checklist general' }}</span>
                    @endif
                </div>
                <div id="etChecklist" class="et-check"
                     data-url="{{ route('inventory.salidas.etapas.checklistIa', [$orden, $etapa->etapa]) }}"
                     data-editable="{{ $puedeEditar ? 1 : 0 }}">
                    <script type="application/json" data-puntos>@json($etapa->checklist ?: [])</script>
                </div>
                @if ($puedeEditar)
                    <div class="et-agregar">
                        <input type="text" id="etNuevoPunto" maxlength="200" placeholder="Agregar un punto que no está (ej. Probar con la fuente de luz del cliente)">
                        <button type="button" class="btn btn--ghost" id="etAgregarPunto">+ Agregar punto</button>
                    </div>
                @endif
            </x-ui.card>

            {{-- ===================== Lo que agrega / piezas con las que sale ===================== --}}
            @if ($extrasTitulo)
                <x-ui.card>
                    <x-ui.section-title style="margin:0 0 4px;">{{ $extrasTitulo }}</x-ui.section-title>
                    <p class="muted et-mini" style="margin:0 0 10px;">{{ $extrasNota }}</p>
                    <div id="etExtras" class="et-extras" data-con-sale="{{ $etapa->etapa === E::EMPAQUE ? 1 : 0 }}" data-editable="{{ $puedeEditar ? 1 : 0 }}">
                        <script type="application/json" data-extras>@json($etapa->extras ?: [])</script>
                    </div>
                    @if ($puedeEditar)
                        <button type="button" class="btn btn--ghost" id="etAgregarExtra" style="margin-top:8px;">+ Agregar {{ $etapa->etapa === E::EMPAQUE ? 'pieza' : 'algo' }}</button>
                    @endif
                </x-ui.card>
            @endif

            {{-- ===================== Entrega: cómo y quién recibe ===================== --}}
            @if ($etapa->etapa === E::ENTREGA)
                <x-ui.card>
                    <x-ui.section-title style="margin:0 0 10px;">¿Cómo se entrega?</x-ui.section-title>
                    <div class="et-modos">
                        @foreach (E::MODALIDADES as $clave => $nombre)
                            <label class="et-modo">
                                <input type="radio" name="modalidad" value="{{ $clave }}" @checked(old('modalidad', $datos['modalidad'] ?? null) === $clave) @disabled(! $puedeEditar)>
                                <span>{{ $nombre }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="et-rejilla" data-si-modo="paqueteria">
                        <x-ui.form-group label="Paquetería" for="etPaq">
                            <input type="text" id="etPaq" name="paqueteria" maxlength="120" value="{{ old('paqueteria', $datos['paqueteria'] ?? '') }}" placeholder="DHL, Estafeta, FedEx…" @disabled(! $puedeEditar)>
                        </x-ui.form-group>
                        <x-ui.form-group label="Número de guía" for="etGuia">
                            <input type="text" id="etGuia" name="guia" maxlength="120" value="{{ old('guia', $datos['guia'] ?? '') }}" data-no-mayus @disabled(! $puedeEditar)>
                        </x-ui.form-group>
                    </div>

                    <div data-si-modo="persona ruta recoge">
                        <x-ui.form-group label="Quién recibe" for="etRecibe">
                            <input type="text" id="etRecibe" name="recibe_nombre" maxlength="255" value="{{ old('recibe_nombre', $datos['recibe_nombre'] ?? '') }}" placeholder="Nombre de quien recibe el equipo" @disabled(! $puedeEditar)>
                        </x-ui.form-group>
                        <div class="et-k" style="margin:8px 0 4px;">Firma de quien recibe</div>
                        @if (! empty($datos['firma_recibe_path']))
                            <img class="et-firma-img" src="{{ \Illuminate\Support\Facades\Storage::disk(config('filesystems.fotos_disk', 'public'))->url($datos['firma_recibe_path']) }}" alt="Firma de quien recibió">
                        @endif
                        @if ($puedeEditar)
                            <canvas class="et-pad" data-pad="firma_recibe" height="150"></canvas>
                            <input type="hidden" name="firma_recibe" id="firma_recibe">
                            <a href="#" class="et-link" data-limpiar="firma_recibe">Limpiar</a>
                        @endif
                    </div>
                </x-ui.card>
            @endif

            {{-- ===================== Notas ===================== --}}
            <x-ui.card>
                <x-ui.section-title style="margin:0 0 8px;">Notas</x-ui.section-title>
                <textarea name="notas" rows="3" maxlength="3000" class="et-area" placeholder="Lo que haya que saber de esta etapa…" @disabled(! $puedeEditar)>{{ old('notas', $etapa->notas) }}</textarea>
            </x-ui.card>
        </div>

        <div class="et-lateral">
            {{-- ===================== Evidencia de la etapa ===================== --}}
            <x-ui.card id="etEvidencia" data-medios="{{ $medios->toJson() }}">
                <div class="et-sec-cab">
                    <x-ui.section-title style="margin:0;">Evidencia</x-ui.section-title>
                    <span class="muted et-mini" data-ev-cuenta>{{ $evidencias->count() }} {{ $evidencias->count() === 1 ? 'archivo' : 'archivos' }}</span>
                </div>
                <p class="muted et-mini" style="margin:4px 0 10px;">Fotos en cualquier formato y de cualquier tamaño, y videos. Se suman a lo anterior.</p>
                @if ($puedeEditar)
                    <div class="et-subir">
                        <label class="btn">
                            + Fotos
                            <input type="file" multiple hidden accept="image/*,.heic,.heif,.tif,.tiff,.dng,.cr2,.cr3,.nef,.arw,.orf,.rw2,.raf,.srw,.pef,.avif,.jxl,.jfif,.webp,.bmp,.psd" data-subir="foto">
                        </label>
                        <label class="btn btn--ghost">
                            Tomar foto
                            <input type="file" hidden accept="image/*" capture="environment" data-subir="foto">
                        </label>
                        <label class="btn btn--ghost">
                            + Video
                            <input type="file" multiple hidden accept="video/mp4,video/quicktime,video/webm,video/x-m4v,video/*" data-subir="video">
                        </label>
                    </div>
                    <div class="et-cola" id="etCola" hidden></div>
                @endif
                <div class="et-galeria" id="etGaleria">
                    @foreach ($evidencias as $i => $ev)
                        @include('structure.gestion_Inventario.ordenes_salida._evidencia', [
                            'evidencia' => $ev, 'borrable' => $puedeEditar && $ev->puedeBorrarla($yo), 'indice' => $i,
                        ])
                    @endforeach
                </div>
                @if ($evidencias->isEmpty() && ! $puedeEditar)
                    <p class="muted et-mini" style="margin:0;">Sin evidencia.</p>
                @endif
            </x-ui.card>

            {{-- ===================== Firma ===================== --}}
            <x-ui.card>
                <x-ui.section-title style="margin:0 0 8px;">Firma de {{ $etapa->etapa === E::ENTREGA ? 'quien entrega' : 'quien hizo la etapa' }}</x-ui.section-title>
                @if ($etapa->firmaUrl())
                    <img class="et-firma-img" src="{{ $etapa->firmaUrl() }}" alt="Firma de la etapa">
                @endif
                @if ($puedeEditar)
                    @if ($usaRegistrada)
                        <label class="et-check-reg">
                            <input type="checkbox" name="usar_firma_registrada" value="1" {{ $etapa->firma_path ? '' : 'checked' }} id="etUsarRegistrada">
                            <span>Usar mi firma registrada</span>
                        </label>
                        <img class="et-firma-img et-firma-reg" src="{{ $yo->firmaUrl() }}" alt="Tu firma registrada">
                    @endif
                    <div id="etPadPropio" @if ($usaRegistrada && ! $etapa->firma_path) hidden @endif>
                        <canvas class="et-pad" data-pad="firma" height="150"></canvas>
                        <input type="hidden" name="firma" id="firma">
                        <a href="#" class="et-link" data-limpiar="firma">Limpiar</a>
                    </div>
                @elseif (! $etapa->firmaUrl())
                    <p class="muted et-mini" style="margin:0;">Sin firma.</p>
                @endif
            </x-ui.card>

            {{-- ===================== Terminar ===================== --}}
            @if ($puedeEditar)
                <x-ui.card class="et-final">
                    @if (! $corrigiendo && $faltantes)
                        <div class="et-k">Para terminar falta</div>
                        <ul class="et-faltan">@foreach ($faltantes as $f)<li>{{ $f }}</li>@endforeach</ul>
                    @endif
                    <div class="et-botones">
                        @if ($corrigiendo)
                            <button type="submit" class="btn" data-accion="guardar">Guardar cambios</button>
                        @else
                            <button type="submit" class="btn btn--ghost" data-accion="guardar">Guardar avance</button>
                            <button type="submit" class="btn" data-accion="terminar">
                                {{ $etapa->etapa === E::ENTREGA ? 'Terminar y dar salida' : 'Terminar '.mb_strtolower($etapa->nombre()) }}
                            </button>
                        @endif
                    </div>
                </x-ui.card>
            @endif
        </div>
    </form>

    {{-- NIP para corregir después de 72 h --}}
    @if ($puedeCorregir && $etapa->terminada() && ! $etapa->enPlazoDeEdicion())
        <dialog class="et-dlg" id="etNip">
            <form method="POST" action="{{ route('inventory.salidas.etapas.desbloquear', [$orden, $etapa->etapa]) }}" class="et-dlg-box">
                @csrf
                <h3>Corregir {{ $etapa->nombre() }}</h3>
                <p class="muted">Pasaron más de {{ E::HORAS_EDICION }} horas desde que se terminó. Escribe tu NIP para corregirla.</p>
                @if (! $yo->approval_pin_hash)
                    <x-ui.alert>No tienes NIP. Pídele a un administrador que te lo asigne en Usuarios.</x-ui.alert>
                @endif
                <input type="password" name="nip" inputmode="numeric" autocomplete="off" maxlength="20" placeholder="Tu NIP" required data-no-mayus>
                <div class="et-dlg-pie">
                    <button type="button" class="btn btn--ghost" data-cerrar>Cancelar</button>
                    <button type="submit" class="btn">Desbloquear</button>
                </div>
            </form>
        </dialog>
    @endif

    @include('partials.galeria')
    @include('structure.gestion_Inventario._subir_fotos')

    <style>
        #etForm [hidden], .et-cola[hidden], .et-firma-reg[hidden] { display:none !important; }
        .et-cab { margin-bottom:16px; }
        .et-top { display:flex; justify-content:space-between; align-items:center; gap:10px 16px; flex-wrap:wrap; }
        .et-id { display:flex; align-items:center; gap:8px 10px; flex-wrap:wrap; min-width:0; }
        .et-acc { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }

        .et-pasos { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:8px; margin:16px 0 0; }
        .et-paso { display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid var(--border); border-radius:12px;
                   color:var(--text); text-decoration:none; background:var(--surface); min-width:0; position:relative; }
        .et-paso:hover { border-color:var(--primary); }
        .et-paso.is-actual { border-color:var(--primary); box-shadow:0 0 0 3px color-mix(in srgb, var(--primary) 16%, transparent); }
        .et-num { width:30px; height:30px; flex:0 0 30px; border-radius:50%; display:grid; place-items:center; font-weight:700;
                  background:var(--surface-2); color:var(--muted); }
        .et-paso.is-en_curso .et-num { background:color-mix(in srgb, var(--primary) 18%, transparent); color:var(--primary); }
        .et-paso.is-terminada .et-num { background:#16a34a; color:#fff; }
        .et-txt { display:flex; flex-direction:column; min-width:0; }
        .et-txt b { font-size:13.5px; } .et-txt small { color:var(--muted); font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

        .et-resp { display:flex; justify-content:space-between; align-items:flex-start; gap:10px 16px; flex-wrap:wrap;
                   margin-top:14px; padding-top:14px; border-top:1px solid var(--border); }
        .et-k { color:var(--muted); font-size:12.5px; }
        .et-v { font-weight:700; }
        .et-desc { margin:4px 0 0; font-size:13px; }
        .et-asig { display:flex; gap:8px; flex-wrap:wrap; }
        .et-asig select { min-width:200px; }

        .et-hecha { margin-bottom:16px; }
        .et-hecha-top { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
        .et-sello { display:inline-block; padding:3px 12px; border:2px solid #16a34a; border-radius:8px; color:#16a34a;
                    font-weight:800; letter-spacing:.08em; text-transform:uppercase; transform:rotate(-3deg); }
        .et-plazo { font-size:11.5px; margin-top:4px; text-align:center; }

        .et-cuerpo { display:grid; grid-template-columns:minmax(0,1fr); gap:16px; align-items:start; }
        @media (min-width:1000px) { .et-cuerpo { grid-template-columns:minmax(0,1fr) 340px; } }
        .et-principal, .et-lateral { display:flex; flex-direction:column; gap:16px; min-width:0; }
        .et-sec-cab { display:flex; justify-content:space-between; align-items:baseline; gap:10px; flex-wrap:wrap; }
        .et-mini { font-size:12.5px; }

        .et-check { margin-top:10px; }
        .et-cargando { padding:22px; border:1px dashed var(--border); border-radius:12px; text-align:center; color:var(--muted); font-size:13.5px; }
        .et-grupo { margin-top:12px; }
        .et-grupo h4 { margin:0 0 6px; font-size:11.5px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); }
        .et-punto { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:6px 12px; align-items:center; padding:9px 0; border-top:1px solid var(--border); }
        .et-punto:first-of-type { border-top:0; }
        .et-punto-txt { font-size:14px; }
        .et-punto-txt .manual { color:var(--muted); font-size:11.5px; margin-left:4px; }
        .et-sno { display:inline-flex; gap:3px; padding:3px; border:1px solid var(--border); border-radius:10px; background:var(--surface); }
        .et-sno label { position:relative; }
        .et-sno input { position:absolute; opacity:0; pointer-events:none; }
        .et-sno span { display:block; padding:5px 10px; border-radius:7px; font-size:12.5px; font-weight:700; color:var(--muted); cursor:pointer; user-select:none; }
        .et-sno input:checked + span.si { background:#16a34a; color:#fff; }
        .et-sno input:checked + span.no { background:var(--danger, #dc2626); color:#fff; }
        .et-sno input:checked + span.na { background:var(--surface-2); color:var(--text); }
        .et-sno input:focus-visible + span { outline:2px solid var(--primary); }
        .et-nota { grid-column:1 / -1; display:flex; gap:6px; align-items:center; }
        .et-nota input { flex:1; min-width:0; padding:7px 10px; border:1px solid var(--border); border-radius:8px; font-size:13px; background:var(--surface); color:var(--text); }
        .et-nota.is-requerida input { border-color:var(--danger, #dc2626); }
        .et-x { border:0; background:none; color:var(--muted); cursor:pointer; font-size:18px; line-height:1; padding:0 4px; }
        .et-resp-txt { font-size:12.5px; font-weight:700; }
        .et-resp-txt.si { color:#16a34a; } .et-resp-txt.no { color:var(--danger, #dc2626); } .et-resp-txt.na, .et-resp-txt.falta { color:var(--muted); }
        .et-agregar { display:flex; gap:8px; margin-top:12px; flex-wrap:wrap; }
        .et-agregar input { flex:1; min-width:200px; padding:9px 11px; border:1px solid var(--border); border-radius:9px; background:var(--surface); color:var(--text); }

        .et-extras { display:flex; flex-direction:column; gap:6px; }
        .et-extra { display:grid; grid-template-columns:auto minmax(0,1fr) 80px auto; gap:8px; align-items:center; }
        .et-extras[data-con-sale="0"] .et-extra { grid-template-columns:minmax(0,1fr) 80px auto; }
        .et-extra input[type=text], .et-extra input[type=number] { width:100%; padding:8px 10px; border:1px solid var(--border); border-radius:8px; background:var(--surface); color:var(--text); font-size:13.5px; }
        .et-extra input[type=checkbox] { width:18px; height:18px; accent-color:#16a34a; }
        .et-vacio { color:var(--muted); font-size:13px; }

        .et-modos { display:grid; grid-template-columns:repeat(auto-fill, minmax(150px,1fr)); gap:8px; margin-bottom:12px; }
        .et-modo { position:relative; }
        .et-modo input { position:absolute; opacity:0; }
        .et-modo span { display:block; padding:11px 12px; border:1px solid var(--border); border-radius:10px; font-weight:600; font-size:13.5px; cursor:pointer; text-align:center; }
        .et-modo input:checked + span { border-color:var(--primary); background:color-mix(in srgb, var(--primary) 10%, transparent); color:var(--primary); }
        .et-rejilla { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:0 12px; }
        @media (max-width:560px) { .et-rejilla { grid-template-columns:minmax(0,1fr); } .et-extra { grid-template-columns:auto minmax(0,1fr) 64px auto; } }

        .et-area { width:100%; padding:10px 12px; border:1px solid var(--border); border-radius:9px; background:var(--surface); color:var(--text); font:inherit; font-size:14px; resize:vertical; }
        .et-subir { display:flex; gap:8px; flex-wrap:wrap; }
        .et-subir .btn { cursor:pointer; }
        .et-cola { display:flex; flex-direction:column; gap:6px; margin-top:10px; }
        .et-fila { font-size:12.5px; padding:7px 10px; border:1px solid var(--border); border-radius:8px; background:var(--surface-2); position:relative; overflow:hidden; }
        .et-fila i { position:absolute; left:0; bottom:0; height:3px; background:var(--primary); }
        .et-fila.is-error { border-color:var(--danger, #dc2626); color:var(--danger, #dc2626); }
        .et-galeria { display:grid; gap:8px; grid-template-columns:repeat(auto-fill, minmax(76px,1fr)); margin-top:10px; }

        .et-pad { display:block; width:100%; height:150px; border:1px dashed var(--border); border-radius:10px; background:#fff; touch-action:none; }
        .et-link { font-size:12.5px; color:var(--primary); }
        .et-firma-img { display:block; max-width:100%; max-height:120px; border:1px solid var(--border); border-radius:10px; background:#fff; margin-bottom:8px; }
        .et-check-reg { display:flex; align-items:center; gap:8px; font-size:13.5px; margin-bottom:8px; }
        .et-check-reg input { width:16px; height:16px; }
        .et-faltan { margin:4px 0 12px; padding-left:18px; font-size:13px; color:var(--muted); }
        .et-botones { display:flex; gap:8px; flex-wrap:wrap; }
        .et-botones .btn { flex:1; justify-content:center; }

        .is-lectura .et-sno { display:none; }

        .et-dlg { padding:0; border:0; background:transparent; }
        .et-dlg::backdrop { background:rgba(2,6,23,.5); }
        .et-dlg-box { width:min(400px, calc(100vw - 28px)); padding:20px; border-radius:16px; background:var(--surface); color:var(--text); border:1px solid var(--border); display:flex; flex-direction:column; gap:10px; }
        .et-dlg-box h3 { margin:0; font-size:17px; }
        .et-dlg-box p { margin:0; font-size:13.5px; }
        .et-dlg-box input { padding:10px 12px; border:1px solid var(--border); border-radius:9px; font-size:16px; letter-spacing:.3em; background:var(--surface); color:var(--text); }
        .et-dlg-pie { display:flex; justify-content:flex-end; gap:8px; }
    </style>

    <script>
    (function () {
        const form = document.getElementById('etForm');
        const editable = form && !form.classList.contains('is-lectura');
        const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

        /* ---------- Checklist ---------- */
        const caja = document.getElementById('etChecklist');
        let puntos = JSON.parse(caja.querySelector('[data-puntos]').textContent || '[]');
        const etiquetas = { si: 'Sí', no: 'No', na: 'N/A' };

        function pintarChecklist() {
            if (!puntos.length) {
                caja.innerHTML = '<div class="et-cargando">Sin checklist todavía.</div>';
                return;
            }
            const grupos = {};
            puntos.forEach(p => (grupos[p.grupo || 'General'] ??= []).push(p));
            caja.innerHTML = Object.entries(grupos).map(([g, lista]) => `
                <div class="et-grupo"><h4>${esc(g)}</h4>
                ${lista.map(p => `
                    <div class="et-punto" data-clave="${esc(p.clave)}">
                        <div class="et-punto-txt">${esc(p.texto)}${p.manual ? '<span class="manual">agregado</span>' : ''}</div>
                        ${editable ? `<div class="et-sno" role="radiogroup" aria-label="${esc(p.texto)}">
                            ${['si', 'no', 'na'].map(r => `<label><input type="radio" name="respuestas[${esc(p.clave)}]" value="${r}" ${p.respuesta === r ? 'checked' : ''}><span class="${r}">${etiquetas[r]}</span></label>`).join('')}
                        </div>` : `<span class="et-resp-txt ${p.respuesta || 'falta'}">${p.respuesta ? etiquetas[p.respuesta] : 'Sin contestar'}</span>`}
                        ${editable ? `<div class="et-nota ${p.respuesta === 'no' ? 'is-requerida' : ''}" ${p.respuesta === 'no' || p.nota ? '' : 'hidden'}>
                            <input type="text" name="notas_punto[${esc(p.clave)}]" maxlength="500" value="${esc(p.nota)}" placeholder="${p.respuesta === 'no' ? '¿Qué está mal? (obligatorio)' : 'Nota'}">
                            ${p.manual ? `<button type="button" class="et-x" data-quitar-punto="${esc(p.clave)}" title="Quitar este punto">×</button>` : ''}
                        </div>` : (p.nota ? `<div class="et-nota muted" style="font-size:12.5px;">${esc(p.nota)}</div>` : '')}
                        ${editable && p.manual && p.respuesta !== 'no' && !p.nota ? `<button type="button" class="et-x" data-quitar-punto="${esc(p.clave)}" title="Quitar este punto" style="grid-column:2">×</button>` : ''}
                    </div>`).join('')}
                </div>`).join('');
        }

        caja.addEventListener('change', e => {
            const r = e.target.closest('input[type=radio]');
            if (!r) return;
            const fila = r.closest('.et-punto');
            const p = puntos.find(x => x.clave === fila.dataset.clave);
            if (p) p.respuesta = r.value;
            const nota = fila.querySelector('.et-nota');
            if (nota) {
                nota.hidden = r.value !== 'no' && !nota.querySelector('input').value;
                nota.classList.toggle('is-requerida', r.value === 'no');
                nota.querySelector('input').placeholder = r.value === 'no' ? '¿Qué está mal? (obligatorio)' : 'Nota';
                if (r.value === 'no') nota.querySelector('input').focus();
            }
        });
        caja.addEventListener('input', e => {
            const n = e.target.closest('.et-nota input');
            if (!n) return;
            const p = puntos.find(x => x.clave === n.closest('.et-punto').dataset.clave);
            if (p) p.nota = n.value;
        });
        caja.addEventListener('click', e => {
            const q = e.target.closest('[data-quitar-punto]');
            if (!q) return;
            const clave = q.dataset.quitarPunto;
            puntos = puntos.filter(p => p.clave !== clave);
            form.insertAdjacentHTML('beforeend', `<input type="hidden" name="quitar_puntos[]" value="${esc(clave)}">`);
            pintarChecklist();
        });

        async function cargarIa() {
            caja.innerHTML = '<div class="et-cargando">Armando el checklist con IA para este equipo… puede tardar unos segundos.</div>';
            try {
                const r = await fetch(caja.dataset.url, { headers: { Accept: 'application/json' } });
                const j = await r.json();
                if (!r.ok) throw new Error(j.message || 'No se pudo armar el checklist.');
                puntos = j.puntos || [];
                if (j.aviso && window.showToast) showToast(j.aviso, 'warn');
            } catch (e) {
                if (window.showToast) showToast(e.message, 'warn');
            }
            pintarChecklist();
        }

        if (!puntos.length && caja.dataset.editable === '1') cargarIa(); else pintarChecklist();

        const nuevo = document.getElementById('etNuevoPunto');
        document.getElementById('etAgregarPunto')?.addEventListener('click', () => {
            const t = nuevo.value.trim();
            if (!t) return nuevo.focus();
            const clave = 'nuevo_' + Date.now();
            puntos.push({ clave, grupo: 'Agregados', texto: t, respuesta: null, nota: null, manual: true, nuevo: true });
            form.insertAdjacentHTML('beforeend', `<input type="hidden" name="puntos_nuevos[]" value="${esc(t)}" data-de="${clave}">`);
            nuevo.value = '';
            pintarChecklist();
            if (window.showToast) showToast('Punto agregado: contéstalo después de guardar.', 'ok');
        });
        nuevo?.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); document.getElementById('etAgregarPunto').click(); } });

        /* ---------- Extras / piezas ---------- */
        const extrasCaja = document.getElementById('etExtras');
        if (extrasCaja) {
            const conSale = extrasCaja.dataset.conSale === '1';
            const edit = extrasCaja.dataset.editable === '1';
            let extras = JSON.parse(extrasCaja.querySelector('[data-extras]').textContent || '[]');
            const pintarExtras = () => {
                if (!extras.length) { extrasCaja.innerHTML = `<div class="et-vacio">${conSale ? 'Sin piezas.' : 'Nada agregado.'}</div>`; return; }
                extrasCaja.innerHTML = extras.map((x, i) => edit ? `
                    <div class="et-extra">
                        ${conSale ? `<input type="checkbox" name="extras[${i}][sale]" value="1" ${x.sale ? 'checked' : ''} title="Sale en el paquete">` : ''}
                        <input type="text" name="extras[${i}][texto]" value="${esc(x.texto)}" maxlength="200" placeholder="${conSale ? 'Pieza' : 'Qué agrega'}">
                        <input type="number" name="extras[${i}][cantidad]" value="${esc(x.cantidad || 1)}" min="1" max="9999" aria-label="Cantidad">
                        <button type="button" class="et-x" data-quitar-extra="${i}" title="Quitar">×</button>
                    </div>` : `
                    <div class="et-extra" style="grid-template-columns:auto minmax(0,1fr) auto;">
                        ${conSale ? `<span class="et-resp-txt ${x.sale ? 'si' : 'falta'}">${x.sale ? '✓' : '—'}</span>` : '<span>•</span>'}
                        <span>${esc(x.texto)}</span><span class="muted">× ${esc(x.cantidad || 1)}</span>
                    </div>`).join('');
            };
            const leer = () => {
                extras = [...extrasCaja.querySelectorAll('.et-extra')].map(f => ({
                    texto: f.querySelector('input[type=text]')?.value || '',
                    cantidad: f.querySelector('input[type=number]')?.value || 1,
                    sale: !!f.querySelector('input[type=checkbox]')?.checked,
                }));
            };
            extrasCaja.addEventListener('click', e => {
                const q = e.target.closest('[data-quitar-extra]');
                if (!q) return;
                leer(); extras.splice(Number(q.dataset.quitarExtra), 1); pintarExtras();
            });
            document.getElementById('etAgregarExtra')?.addEventListener('click', () => {
                leer(); extras.push({ texto: '', cantidad: 1, sale: true }); pintarExtras();
                extrasCaja.querySelector('.et-extra:last-child input[type=text]')?.focus();
            });
            pintarExtras();
            // Sin renglones, el servidor recibe una lista vacía (y no conserva la anterior).
            form?.addEventListener('submit', () => { if (edit && !extrasCaja.querySelector('.et-extra')) form.insertAdjacentHTML('beforeend', '<input type="hidden" name="extras" value="">'); });
        }

        /* ---------- Entrega: campos según la modalidad ---------- */
        const modos = form?.querySelectorAll('input[name=modalidad]');
        const ajustarModo = () => {
            const m = form.querySelector('input[name=modalidad]:checked')?.value;
            form.querySelectorAll('[data-si-modo]').forEach(b => b.hidden = !m || !b.dataset.siModo.split(' ').includes(m));
            dispatchEvent(new Event('resize'));
        };
        if (modos?.length) { modos.forEach(m => m.addEventListener('change', ajustarModo)); ajustarModo(); }

        /* ---------- Firmas ---------- */
        form?.querySelectorAll('canvas[data-pad]').forEach(lienzo => {
            const input = document.getElementById(lienzo.dataset.pad);
            const ctx = lienzo.getContext('2d');
            let firmando = false;
            const ajustar = () => {
                const ancho = Math.round(lienzo.getBoundingClientRect().width);
                if (!ancho) return;
                const previo = input.value;
                lienzo.width = Math.max(200, ancho); lienzo.height = 150;
                ctx.lineWidth = 2.2; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#111827';
                if (previo) { const img = new Image(); img.onload = () => ctx.drawImage(img, 0, 0, lienzo.width, lienzo.height); img.src = previo; }
            };
            ajustar(); addEventListener('resize', ajustar);
            const punto = e => { const r = lienzo.getBoundingClientRect(); const p = e.touches ? e.touches[0] : e; return { x: (p.clientX - r.left) * (lienzo.width / r.width), y: (p.clientY - r.top) * (lienzo.height / r.height) }; };
            lienzo.addEventListener('mousedown', e => { e.preventDefault(); firmando = true; const p = punto(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); });
            lienzo.addEventListener('mousemove', e => { if (!firmando) return; const p = punto(e); ctx.lineTo(p.x, p.y); ctx.stroke(); });
            addEventListener('mouseup', () => { if (firmando) { firmando = false; input.value = lienzo.toDataURL('image/png'); } });
            lienzo.addEventListener('touchstart', e => { e.preventDefault(); firmando = true; const p = punto(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); }, { passive: false });
            lienzo.addEventListener('touchmove', e => { if (!firmando) return; e.preventDefault(); const p = punto(e); ctx.lineTo(p.x, p.y); ctx.stroke(); }, { passive: false });
            lienzo.addEventListener('touchend', () => { if (firmando) { firmando = false; input.value = lienzo.toDataURL('image/png'); } });
            form.querySelector(`[data-limpiar="${lienzo.dataset.pad}"]`)?.addEventListener('click', e => { e.preventDefault(); ctx.clearRect(0, 0, lienzo.width, lienzo.height); input.value = ''; });
        });
        const usarReg = document.getElementById('etUsarRegistrada');
        usarReg?.addEventListener('change', () => {
            document.getElementById('etPadPropio').hidden = usarReg.checked;
            document.querySelector('.et-firma-reg').hidden = !usarReg.checked;
            dispatchEvent(new Event('resize'));
        });
        if (usarReg) document.querySelector('.et-firma-reg').hidden = !usarReg.checked;

        /* ---------- Guardar / terminar ---------- */
        form?.querySelectorAll('[data-accion]').forEach(b => b.addEventListener('click', async e => {
            document.getElementById('etAccion').value = b.dataset.accion;
            if (b.dataset.accion === 'terminar') {
                e.preventDefault();
                const ok = await confirmModal({
                    title: b.textContent.trim(),
                    message: @json($etapa->etapa === \App\Models\OrdenSalidaEtapa::ENTREGA
                        ? 'Se da por entregado el equipo y se avisa al asesor. Después podrás corregirla 72 h sin NIP.'
                        : 'Se avisa al asesor, a los administradores y a quien sigue. Después podrás corregirla 72 h sin NIP.'),
                    confirmText: 'Sí, terminar', danger: false,
                });
                if (ok) form.submit();
            }
        }));

        /* ---------- NIP ---------- */
        const dlg = document.getElementById('etNip');
        document.querySelector('[data-pedir-nip]')?.addEventListener('click', () => { dlg.showModal(); dlg.querySelector('input[name=nip]').focus(); });
        dlg?.querySelector('[data-cerrar]').addEventListener('click', () => dlg.close());

        /* ---------- Evidencia de la etapa ---------- */
        const ETAPA = @json($etapa->etapa);
        const URL_FOTO = @json(route('inventory.salidas.evidencias.foto', $orden));
        const URL_CHUNK = @json(route('inventory.salidas.evidencias.fotoChunk', $orden));
        const URL_VIDEO = @json(route('inventory.salidas.evidencias.videoChunk', $orden));
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token());
        const cola = document.getElementById('etCola');
        const galeria = document.getElementById('etGaleria');
        const tarjeta = document.getElementById('etEvidencia');
        const pendientes = [];
        let trabajando = false;

        async function post(url, datos) {
            const r = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }, body: datos });
            const j = await r.json().catch(() => ({}));
            if (!r.ok) throw new Error(j.message || (j.errors ? Object.values(j.errors).flat()[0] : 'No se pudo subir.'));
            return j;
        }

        async function subirFoto(t) {
            const archivo = await ObsFotos.preparar(t.archivo);
            const armada = await ObsFotos.subir(archivo, URL_CHUNK, pct => avance(t, pct * 0.95, 'Subiendo ' + pct + '%'));
            const d = new FormData();
            d.append('foto_path', armada.foto_path); d.append('nombre', archivo.name || 'foto'); d.append('etapa', ETAPA);
            return post(URL_FOTO, d);
        }

        async function subirVideo(t) {
            const a = t.archivo;
            const ext = ((a.name.split('.').pop() || '') + '').toLowerCase();
            const extension = ['mp4', 'mov', 'webm', 'm4v'].includes(ext) ? ext : ({ 'video/quicktime': 'mov', 'video/webm': 'webm', 'video/x-m4v': 'm4v' }[a.type] || 'mp4');
            const id = (crypto.randomUUID ? crypto.randomUUID() : Date.now() + '-' + Math.random().toString(36).slice(2)).replace(/[^a-zA-Z0-9-]/g, '');
            const PED = 4 * 1024 * 1024, total = Math.max(1, Math.ceil(a.size / PED));
            let j = null;
            for (let i = 0; i < total; i++) {
                const d = new FormData();
                d.append('chunk', a.slice(i * PED, (i + 1) * PED), 'chunk');
                d.append('upload_id', id); d.append('index', i); d.append('total', total);
                d.append('extension', extension); d.append('nombre', a.name || 'video.' + extension); d.append('etapa', ETAPA);
                j = await post(URL_VIDEO, d);
                avance(t, Math.round((i + 1) / total * 100), 'Subiendo video ' + Math.round((i + 1) / total * 100) + '%');
            }
            return j;
        }

        function avance(t, pct, texto) { t.fila.querySelector('i').style.width = pct + '%'; t.fila.querySelector('span').textContent = t.archivo.name + ' · ' + texto; }

        async function procesar() {
            if (trabajando) return;
            trabajando = true;
            while (pendientes.length) {
                const t = pendientes.shift();
                try {
                    const j = t.tipo === 'video' ? await subirVideo(t) : await subirFoto(t);
                    if (j && j.html) galeria.insertAdjacentHTML('beforeend', j.html);
                    t.fila.remove();
                    sincronizar();
                } catch (e) {
                    t.fila.classList.add('is-error');
                    t.fila.querySelector('span').textContent = t.archivo.name + ' · ' + e.message;
                }
            }
            trabajando = false;
            cola.hidden = !cola.children.length;
        }

        function sincronizar() {
            const items = [...galeria.querySelectorAll('.ev-item')];
            tarjeta.dataset.medios = JSON.stringify(items.map(c => ({ tipo: c.dataset.tipo === 'video' ? 'video' : 'foto', url: c.dataset.url })));
            items.forEach((c, i) => { const b = c.querySelector('[data-galeria]'); if (b) b.dataset.indice = i; });
            const n = items.length;
            tarjeta.querySelector('[data-ev-cuenta]').textContent = n + (n === 1 ? ' archivo' : ' archivos');
        }

        document.querySelectorAll('[data-subir]').forEach(input => input.addEventListener('change', () => {
            [...input.files].forEach(archivo => {
                const fila = document.createElement('div');
                fila.className = 'et-fila';
                fila.innerHTML = '<span></span><i style="width:0"></i>';
                fila.querySelector('span').textContent = archivo.name + ' · en espera';
                cola.appendChild(fila); cola.hidden = false;
                pendientes.push({ archivo, tipo: input.dataset.subir, fila });
            });
            input.value = '';
            procesar();
        }));

        // Quitar evidencia (mismo endpoint que en la orden).
        galeria?.addEventListener('click', async e => {
            const b = e.target.closest('[data-borrar]');
            if (!b) return;
            e.preventDefault();
            if (!await confirmModal({ title: 'Quitar evidencia', message: 'Se borra el archivo. No se puede deshacer.', confirmText: 'Quitar', danger: true })) return;
            const r = await fetch(b.dataset.borrar, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } });
            if (r.ok) { b.closest('.ev-item').remove(); sincronizar(); } else if (window.showToast) showToast('No se pudo quitar.', 'warn');
        });
    })();
    </script>
@endsection
