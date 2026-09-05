{{--
    Formulario compartido de documento comercial (cotización / venta).
    Variables esperadas:
      $accion      URL del form (store/update)
      $metodo      'POST' | 'PUT'
      $initial     array de estado inicial (cliente, items, pagos, fichas, montos...)
      $rClientes   URL endpoint buscar clientes
      $rProductos  URL endpoint buscar productos
      $rFichas     URL endpoint buscar fichas
      $backRoute   URL del botón "Regresar"
      $titulo      título del header
      $subtitulo   subtítulo del header
      $textoGuardar (opcional) texto del botón guardar
--}}
@php
    $textoGuardar = $textoGuardar ?? 'Guardar';
    $avisosStock = $avisosStock ?? [];
@endphp

{{-- Cabecera estandar del sistema. El guardado vive al pie del formulario. --}}
<x-ui.page-header :title="$titulo" :subtitle="$subtitulo" :back="$backRoute">
    <x-slot:icon>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
    </x-slot:icon>
</x-ui.page-header>

@if ($errors->any())
    <div class="erp-card pad" style="margin-bottom:20px; border-color:var(--danger); background:var(--danger-soft);">
        <b style="color:var(--danger);">Revisa los siguientes puntos:</b>
        <ul style="margin:8px 0 0; padding-left:18px; color:var(--danger); font-size:13px;">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

@if (! empty($avisosStock))
    <div class="erp-card pad" style="margin-bottom:20px; border-color:#d97706; background:rgba(217,119,6,.1);">
        <b style="color:#d97706;">Ojo con el stock: no hay suficiente inventario para esta venta</b>
        <ul style="margin:8px 0 0; padding-left:18px; color:#d97706; font-size:13px;">
            @foreach ($avisosStock as $aviso)<li>{{ $aviso }}</li>@endforeach
        </ul>
    </div>
@endif

<form id="cotForm" method="POST" action="{{ $accion }}">
    @csrf
    @if ($metodo === 'PUT') @method('PUT') @endif
    <div id="hiddenInputs"></div>

    <div class="cot-grid">
        {{-- ===================== Columna izquierda ===================== --}}
        <div style="display:flex; flex-direction:column; gap:18px;">
            <div class="cot-panel">
                <div class="cot-panel-head cot-blue">Cliente</div>
                <div class="cot-panel-body">
                    <div style="position:relative;">
                        <input type="text" id="clienteSearch" class="cot-input" placeholder="Buscar cliente..." autocomplete="off">
                        <div id="clienteResults" class="cot-ac"></div>
                    </div>
                    <div id="clienteSelected" style="display:none; margin-top:10px;" class="cot-chip-box"></div>
                </div>
            </div>

            <div class="cot-panel">
                <div class="cot-panel-head">Detalles de la propuesta</div>
                <div class="cot-panel-body">
                    <label class="cot-lbl">Congreso</label>
                    <select id="congreso_id" class="cot-input">
                        <option value="">Sin congreso</option>
                        @foreach ($congresos as $congresoOpcion)
                            <option value="{{ $congresoOpcion->id }}">{{ $congresoOpcion->nombre }}</option>
                        @endforeach
                    </select>
                    <p class="cot-hint">Déjalo en «Sin congreso» si no se levantó en uno; entonces no aparece en el PDF.</p>

                    <label class="cot-lbl" style="margin-top:14px;">Garantía del equipo</label>
                    <select id="garantia_meses" class="cot-input">
                        @foreach (\App\Models\Venta::GARANTIAS as $meses)
                            <option value="{{ $meses }}">{{ $meses }} meses</option>
                        @endforeach
                    </select>
                    <p class="cot-hint">Se imprime en la carta garantía que se entrega con el equipo.</p>

                    <label class="cot-lbl" style="margin-top:14px;">Nota al cliente</label>
                    <textarea id="nota_cliente" class="cot-input" rows="3" placeholder="Opcional..." style="resize:vertical;"></textarea>
                </div>
            </div>

            <div class="cot-panel">
                <div class="cot-panel-head">Ficha técnica</div>
                <div class="cot-panel-body">
                    <div style="position:relative;">
                        <input type="text" id="fichaSearch" class="cot-input" placeholder="Buscar ficha para anexar al PDF..." autocomplete="off">
                        <div id="fichaResults" class="cot-ac"></div>
                    </div>
                    <div id="fichasSelected" style="display:flex; flex-wrap:wrap; gap:8px; margin-top:10px;"></div>
                </div>
            </div>
        </div>

        {{-- ===================== Columna derecha ===================== --}}
        <div style="display:flex; flex-direction:column; gap:18px;">
            <div class="cot-panel">
                <div class="cot-panel-head" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
                    <span>Productos</span>
                    <span style="font-size:12px; color:var(--muted); font-weight:500;">Marca <b style="color:var(--green);">Regalo</b> para excluir del total.</span>
                </div>
                <div class="cot-panel-body" style="padding-bottom:6px;">
                    <div style="position:relative;">
                        <svg style="position:absolute; left:13px; top:50%; transform:translateY(-50%); width:17px; height:17px; color:var(--muted); pointer-events:none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" id="prodSearch" class="cot-input" placeholder="Buscar producto o paquete para agregar..." autocomplete="off" style="padding-left:40px;">
                        <div id="prodResults" class="cot-ac"></div>
                    </div>
                </div>
                <div class="cot-panel-body" style="overflow-x:auto; padding-top:6px;">
                    <table class="cot-table">
                        <thead>
                            <tr>
                                <th>Imagen</th><th>Equipo</th><th>Modelo</th><th>Marca</th>
                                <th>Stock</th><th>Cantidad</th><th>Subtotal</th><th>Sobreprecio</th><th>Regalo</th><th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <tr id="itemsEmpty"><td colspan="10" style="text-align:center; color:var(--muted); padding:22px;">Busca y agrega productos arriba.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="cot-panel">
                <div class="cot-panel-body">
                    <div class="cot-totes">
                        <div class="cot-tote-row">
                            <label>Envío</label>
                            <input type="text" id="envio" class="cot-input cot-num" inputmode="decimal" value="0">
                        </div>
                        <div class="cot-tote-row">
                            <label>Descuento</label>
                            <div style="display:flex; gap:8px;">
                                <select id="descuento_tipo" class="cot-input" style="max-width:130px;">
                                    <option value="porcentaje">%</option>
                                    <option value="monto">$ monto</option>
                                </select>
                                <input type="text" id="descuento_valor" class="cot-input cot-num" inputmode="decimal" value="0">
                            </div>
                        </div>
                        <label style="display:flex; align-items:center; gap:8px; margin:6px 0;">
                            <input type="checkbox" id="aplica_iva"> Aplicar IVA (16%)
                        </label>
                        <div class="cot-line"><span>Subtotal</span><b id="tSubtotal">$0.00</b></div>
                        <div class="cot-line"><span>Descuento</span><b id="tDescuento">-$0.00</b></div>
                        <div class="cot-line"><span>IVA</span><b id="tIva">$0.00</b></div>
                        <div class="cot-line cot-line-strong"><span>Total</span><b id="tTotal">$0.00</b></div>

                        <div class="cot-tote-row" style="margin-top:12px;">
                            <label>Valor a cuenta (equipos / mercancía a cuenta)</label>
                            <input type="text" id="valor_a_cuenta" class="cot-input cot-num" inputmode="decimal" value="0">
                        </div>
                        <p style="font-size:12px; color:var(--muted); margin:6px 0 0;">Se resta del total para calcular el contrato.</p>
                        <div class="cot-line cot-line-strong"><span>Total del contrato</span><b id="tContrato">$0.00</b></div>
                    </div>
                </div>
            </div>

            <div class="cot-panel">
                <div class="cot-panel-head">Forma de pago</div>
                <div class="cot-panel-body">
                    <input type="hidden" id="modalidad" value="contado">
                    <div class="cot-seg" role="tablist">
                        <button type="button" class="cot-seg-btn" data-mod="contado">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                            De contado
                        </button>
                        <button type="button" class="cot-seg-btn" data-mod="financiamiento">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><line x1="6" y1="15" x2="10" y2="15"/></svg>
                            En pagos
                        </button>
                    </div>
                    <div id="pagosBlock" style="display:none; margin-top:16px;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pie de acciones: acompana el scroll y queda siempre a la mano. --}}
    <div class="page-foot">
        <a href="{{ $backRoute }}" class="btn btn--ghost">Cancelar</a>
        <button type="submit" form="cotForm" class="btn">{{ $textoGuardar }}</button>
    </div>
</form>

<style>
    .cot-grid { display:grid; grid-template-columns:360px minmax(0,1fr); gap:18px; align-items:start; }
    .cot-grid > div { min-width:0; }
    @media (max-width:1100px){ .cot-grid { grid-template-columns:1fr; } }
    .cot-panel { border:1px solid var(--border); border-radius:14px; background:var(--surface); }
    .cot-panel-head { padding:18px 20px 2px; font-weight:600; color:var(--text); font-size:14px; letter-spacing:-.01em; }
    .cot-blue { color:var(--text); }
    .cot-panel-body { padding:13px 20px 20px; }
    .cot-input { width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:9px; font-size:15px;
                 background:var(--surface); color:var(--text); outline:none; font-family:inherit; }
    .cot-input:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,122,255,.15); }
    .cot-num { text-align:right; max-width:160px; }
    .cot-lbl { display:block; font-size:12px; font-weight:600; color:var(--muted); margin-bottom:5px; }
    .cot-hint { margin:7px 0 0; font-size:11.5px; color:var(--muted); line-height:1.45; }
    .cot-ac { position:absolute; top:calc(100% + 4px); left:0; right:0; background:var(--surface); border:1px solid var(--border);
              border-radius:12px; box-shadow:0 12px 32px rgba(17,24,39,.14); z-index:50; max-height:280px; overflow-y:auto; display:none; }
    .cot-ac.open { display:block; }
    .cot-ac-item { padding:10px 12px; cursor:pointer; border-bottom:1px solid var(--border); font-size:14px; }
    .cot-ac-item:last-child { border-bottom:none; }
    .cot-ac-item:hover { background:var(--surface-2); }
    .cot-ac-item small { display:block; color:var(--muted); font-size:12px; }
    .cot-chip-box { border:1px solid var(--border); border-radius:12px; padding:12px; background:var(--surface-2); }
    .cot-chip { display:inline-flex; align-items:center; gap:8px; background:var(--primary-soft); color:var(--primary);
                padding:6px 10px; border-radius:999px; font-size:13px; font-weight:600; }
    .cot-chip button { background:none; border:none; color:inherit; cursor:pointer; font-size:15px; line-height:1; padding:0; }
    .cot-table { width:100%; border-collapse:collapse; font-size:13px; color:var(--text); }
    .cot-table th { text-align:left; padding:10px 8px; color:var(--muted); font-size:11px; text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid var(--border); white-space:nowrap; }
    .cot-table td { padding:10px 8px; border-bottom:1px solid var(--border); vertical-align:middle; }
    .cot-table .cot-cell-num { width:70px; padding:7px 8px; text-align:right; }
    .cot-thumb { width:40px; height:40px; border-radius:8px; object-fit:cover; border:1px solid var(--border); background:var(--surface-2); display:flex; align-items:center; justify-content:center; color:var(--muted); }

    /* Ficha del cliente elegido */
    .cli-cab { display:flex; align-items:flex-start; gap:10px; }
    .cli-cab b { font-size:14.5px; }
    .cli-cat { display:inline-block; margin-left:7px; padding:2px 8px; border-radius:999px;
               background:var(--surface); border:1px solid var(--border);
               color:var(--muted); font-size:11.5px; font-weight:600; vertical-align:middle; }
    .cli-baja { display:inline-block; margin-left:6px; padding:2px 8px; border-radius:999px;
                background:var(--danger-soft); color:var(--danger); font-size:11.5px; font-weight:700; }

    .cli-datos { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr));
                 gap:8px 14px; margin-top:12px; }
    .cli-dato .e { display:block; color:var(--muted); font-size:11px; font-weight:700;
                   letter-spacing:.05em; text-transform:uppercase; }
    .cli-dato .v { display:block; margin-top:1px; font-size:13px; overflow-wrap:anywhere; }

    .cli-nota { margin:12px 0 0; padding:8px 11px; border-radius:8px; background:var(--surface);
                border:1px solid var(--border); color:var(--muted); font-size:12.5px; line-height:1.45; }

    .cli-historial { display:flex; flex-wrap:wrap; gap:14px; margin-top:12px; padding-top:10px;
                     border-top:1px solid var(--border); color:var(--muted); font-size:12.5px; }
    .cli-historial b { color:var(--text); }

    /* Que deba dinero es lo primero que hay que ver antes de venderle más. */
    .cli-debe { margin:10px 0 0; padding:9px 12px; border-radius:8px;
                background:var(--danger-soft); color:var(--danger); font-size:13px; font-weight:600; }
    .cli-alcorriente { margin:10px 0 0; color:var(--green); font-size:12.5px; font-weight:600; }

    /* Elegir cuáles piezas se entregan */
    .cot-link { padding:0; border:0; background:none; color:var(--primary); font-family:inherit;
                font-size:12px; font-weight:600; cursor:pointer; text-decoration:underline; }
    .cot-piezas { display:grid; grid-template-columns:repeat(auto-fill, minmax(190px, 1fr)); gap:8px; }
    .cot-pieza { display:flex; align-items:center; gap:9px; padding:8px 11px; cursor:pointer;
                 border:1px solid var(--border); border-radius:9px; background:var(--surface);
                 transition:border-color .14s ease, background .14s ease; }
    .cot-pieza:hover { border-color:var(--muted); }
    .cot-pieza.puesta { border-color:var(--primary); background:var(--primary-soft); }
    .cot-pieza input { flex:none; width:15px; height:15px; margin:0; cursor:pointer; accent-color:var(--primary); }
    .cot-pieza .cod { font-family:ui-monospace, Consolas, monospace; font-size:12.5px; font-weight:700; }
    .cot-pieza .det { color:var(--muted); font-size:11.5px; }
    .cot-totes label { font-weight:600; }
    .cot-tote-row { display:flex; align-items:center; justify-content:space-between; gap:12px; margin:8px 0; }
    .cot-line { display:flex; justify-content:space-between; align-items:center; padding:7px 0; font-size:14px; color:var(--text); border-top:1px dashed var(--border); }
    .cot-line-strong { font-size:17px; font-weight:800; border-top:1px solid var(--border); margin-top:4px; padding-top:12px; }
    .cot-pay { border:1px solid var(--border); border-radius:14px; padding:14px; background:var(--surface); }
    .cot-pay-top { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; }
    .cot-pay-name { font-weight:700; font-size:15px; }
    .cot-pay-date { color:var(--muted); font-size:13px; margin-top:2px; }
    .cot-pay-amt { font-weight:800; font-size:16px; }
    .cot-pay-controls { display:flex; align-items:center; gap:10px; margin-top:12px; flex-wrap:wrap; }
    .cot-btn-x { background:transparent; color:var(--muted); border:1px solid var(--border); border-radius:9px; padding:6px 11px; cursor:pointer; font-weight:600; font-size:13px;
                 transition:background .14s ease, color .14s ease, border-color .14s ease, transform .12s var(--erp-ease); }
    .cot-btn-x:hover { background:var(--danger-soft); color:var(--danger); border-color:transparent; }
    .cot-btn-x:active { transform:scale(.96); }
    .cot-toggle-lbl { display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:600; color:var(--muted); }
    .cot-regalo { display:inline-flex; align-items:center; }

    /* Botón de ícono minimalista (acciones de fila / pagos) */
    .cot-ico-btn { width:34px; height:34px; border-radius:9px; border:1px solid var(--border); background:var(--surface); color:var(--muted);
                   cursor:pointer; display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto;
                   transition:background .14s ease, color .14s ease, border-color .14s ease, transform .12s var(--erp-ease); }
    .cot-ico-btn:hover { background:var(--surface-2); color:var(--text); }
    .cot-ico-btn:active { transform:scale(.9); }
    .cot-ico-btn.danger { background:var(--danger-soft); color:var(--danger); border-color:transparent; }
    .cot-ico-btn.danger:hover { background:var(--danger); color:#fff; }
    .cot-ico-btn svg { width:16px; height:16px; }

    /* Interruptor Regalo (píldora) */
    .cot-switch { display:inline-flex; align-items:center; gap:7px; cursor:pointer; font-size:12.5px; font-weight:600; color:var(--muted); user-select:none; }
    .cot-switch input { position:absolute; opacity:0; width:0; height:0; }
    .cot-switch .track { width:38px; height:22px; border-radius:999px; background:var(--surface-2); border:1px solid var(--border); position:relative; transition:background .18s var(--erp-ease), border-color .18s ease; flex:0 0 auto; }
    .cot-switch .track::after { content:""; position:absolute; top:2px; left:2px; width:16px; height:16px; border-radius:50%; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.25); transition:transform .2s var(--erp-ease); }
    .cot-switch input:checked + .track { background:var(--green); border-color:transparent; }
    .cot-switch input:checked + .track::after { transform:translateX(16px); }
    .cot-switch input:checked ~ .lbl { color:var(--green); }

    /* Toggle "fijo/auto" de un pago */
    .cot-lock { display:inline-flex; align-items:center; gap:7px; padding:6px 11px; border-radius:9px; border:1px solid var(--border);
                background:var(--surface); color:var(--muted); cursor:pointer; font-size:12.5px; font-weight:600; font-family:inherit;
                transition:background .14s ease, color .14s ease, border-color .14s ease, transform .12s var(--erp-ease); }
    .cot-lock:hover { background:var(--surface-2); color:var(--text); }
    .cot-lock:active { transform:scale(.96); }
    .cot-lock.on { background:var(--primary-soft); color:var(--primary); border-color:transparent; }
    .cot-lock svg { width:14px; height:14px; }

    /* Botón "agregar" con borde punteado */
    .cot-add { display:inline-flex; align-items:center; gap:6px; padding:9px 13px; border-radius:10px; border:1px dashed var(--border);
               background:transparent; color:var(--primary); cursor:pointer; font-size:13px; font-weight:600; font-family:inherit;
               transition:background .14s ease, border-color .14s ease, transform .12s var(--erp-ease); }
    .cot-add:hover { background:var(--primary-soft); border-color:transparent; }
    .cot-add:active { transform:scale(.97); }

    /* Encabezado y resumen del plan de pagos */
    .cot-pay-head { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:4px; }
    .cot-pay-head b { font-size:14px; }
    .cot-pay-pct { color:var(--muted); font-size:12px; margin-top:2px; }
    .cot-pay-mini { display:flex; align-items:center; gap:5px; }
    .cot-pay-mini input { width:64px; text-align:right; padding:7px 8px; border:1px solid var(--border); border-radius:8px; font-size:13px; background:var(--surface); color:var(--text); outline:none; }
    .cot-pay-mini input:focus { border-color:var(--primary); }
    .cot-pay-mini span { font-size:12px; color:var(--muted); }
    .cot-pay-sum { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px 14px; border-radius:12px;
                   background:var(--surface-2); color:var(--muted); font-size:13.5px; font-weight:600; }
    .cot-pay-sum.ok { background:var(--green-soft); color:var(--green); }
    .cot-pay-sum.bad { background:var(--danger-soft); color:var(--danger); }

    /* Control segmentado (contado / en pagos) */
    .cot-seg { display:flex; background:var(--surface-2); border:1px solid var(--border); border-radius:12px; padding:4px; gap:4px; }
    .cot-seg-btn { flex:1; display:inline-flex; align-items:center; justify-content:center; gap:7px; border:none; background:transparent;
                   color:var(--muted); font-weight:600; font-size:13.5px; padding:9px 12px; border-radius:9px; cursor:pointer; font-family:inherit;
                   transition:background .15s ease, color .15s ease, box-shadow .15s ease, transform .12s var(--erp-ease); }
    .cot-seg-btn:active { transform:scale(.98); }
    .cot-seg-btn.on { background:var(--surface); color:var(--primary); box-shadow:0 1px 4px rgba(17,24,39,.10); }

    /* Stepper de meses */
    .cot-step { display:inline-flex; align-items:center; border:1px solid var(--border); border-radius:10px; overflow:hidden; background:var(--surface); }
    .cot-step button { width:32px; height:32px; border:none; background:transparent; color:var(--text); cursor:pointer; font-size:17px; line-height:1;
                       display:flex; align-items:center; justify-content:center; transition:background .13s ease; }
    .cot-step button:hover { background:var(--surface-2); }
    .cot-step button:active { background:var(--primary-soft); }
    .cot-step .val { min-width:38px; text-align:center; font-weight:700; font-size:14px; border-left:1px solid var(--border); border-right:1px solid var(--border); align-self:stretch; display:flex; align-items:center; justify-content:center; }

    /* Contenedor y filas del plan */
    .cot-plan { border:1px solid var(--border); border-radius:14px; }
    .cot-plan-head { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px 14px; border-bottom:1px solid var(--border); }
    .cot-plan-head b { font-size:14px; }
    .cot-prow { display:flex; align-items:center; gap:10px 12px; padding:12px 14px; border-bottom:1px solid var(--border); flex-wrap:wrap; }
    .cot-prow-num { width:26px; height:26px; border-radius:50%; background:var(--primary-soft); color:var(--primary); font-size:12px; font-weight:800;
                    display:flex; align-items:center; justify-content:center; flex:0 0 auto; }
    .cot-prow-info { flex:1; min-width:110px; }
    .cot-prow-name { font-weight:600; font-size:14px; line-height:1.2; }
    .cot-prow-date { color:var(--muted); font-size:12px; margin-top:2px; }
    .cot-plan-foot { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 14px; flex-wrap:wrap; }
</style>

<script>
(function () {
    const ROUTES = { clientes: @json($rClientes), productos: @json($rProductos), fichas: @json($rFichas) };
    const INITIAL = @json($initial);
    const IVA = 0.16;

    const ICON_TRASH = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>';
    const ICON_LOCK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
    const ICON_UNLOCK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>';
    const ICON_X = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

    const state = {
        customer: INITIAL.customer,
        items: (INITIAL.items || []).map(i => ({
            tipo_item: i.tipo_item, equipo_id: i.equipo_id, paquete_id: i.paquete_id, producto_id: i.producto_id,
            nombre: i.nombre, modelo: i.modelo, marca: i.marca, imagen: i.imagen,
            cantidad: +i.cantidad || 1, precio_unitario: +i.precio_unitario || 0,
            sobreprecio: +i.sobreprecio || 0, es_regalo: !!i.es_regalo,
            stock: +i.stock || 0, piezas: i.piezas || [], seriales: i.seriales || [], abierto: false,
        })),
        pagos: (INITIAL.pagos || []).map(p => ({ ...p, porcentaje:+p.porcentaje, monto:+p.monto, bloqueado:!!p.bloqueado })),
        fichas: INITIAL.fichas || [],
        meses: (+INITIAL.num_meses > 0 ? +INITIAL.num_meses : 2),
        // Automático: las fechas se calculan solas cada "frecuenciaDias" días.
        // Manual: cada pago tiene su propia fecha, editable libremente.
        modoPagos: 'automatico',
        frecuenciaDias: 30,
    };

    const money = n => '$' + (Math.round((+n || 0) * 100) / 100).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const $ = id => document.getElementById(id);
    const num = v => { const n = parseFloat(String(v).replace(/[^0-9.\-]/g, '')); return isNaN(n) ? 0 : n; };
    const MESES_ABBR = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    const fmtFecha = s => { if (!s) return ''; const d = new Date(s + 'T00:00:00'); return `${String(d.getDate()).padStart(2,'0')} ${MESES_ABBR[d.getMonth()]} ${d.getFullYear()}`; };

    /* ==========================================================
       Stock y piezas

       Un paquete no tiene stock propio (lo tienen los productos que lo
       componen), así que ahí no se muestra nada.
    ========================================================== */
    function celdaStock(it, idx) {
        if (it.tipo_item !== 'producto') return '<span style="color:var(--muted);">—</span>';

        const stock = +it.stock || 0;
        const elegidas = (it.seriales || []).length;
        const falta = it.cantidad > stock;

        const color = falta ? 'var(--danger)' : (stock > 0 ? 'var(--green)' : 'var(--muted)');
        const texto = `<b style="color:${color};">${stock}</b> <span style="color:var(--muted); font-size:12px;">disp.</span>`;

        // Sin piezas identificadas no hay nada que elegir.
        if (!(it.piezas || []).length) return texto;

        const etiqueta = elegidas ? `${elegidas} elegida(s)` : 'Elegir';

        return `${texto}<br><button type="button" class="cot-link" data-piezas="${idx}">${etiqueta}</button>`;
    }

    function filaDePiezas(it, idx) {
        const tr = document.createElement('tr');
        const elegidas = it.seriales || [];

        const chips = it.piezas.map(p => {
            const puesta = elegidas.includes(p.id);
            const detalle = [p.no_serie, p.condicion === 'usado' ? 'usado' : null].filter(Boolean).join(' · ');

            return `<label class="cot-pieza ${puesta ? 'puesta' : ''}">
                        <input type="checkbox" data-pieza="${idx}" value="${p.id}" ${puesta ? 'checked' : ''}>
                        <span class="cod">${p.codigo || 'sin etiqueta'}</span>
                        ${detalle ? `<span class="det">${detalle}</span>` : ''}
                    </label>`;
        }).join('');

        const sobran = elegidas.length > it.cantidad;

        tr.innerHTML = `
            <td colspan="10" style="background:var(--surface-2); padding:14px 16px;">
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:10px;">
                    <b style="font-size:13px;">¿Cuáles piezas se entregan?</b>
                    <span style="color:${sobran ? 'var(--danger)' : 'var(--muted)'}; font-size:12.5px;">
                        ${elegidas.length} de ${it.cantidad} elegida(s)${sobran ? ' — sobran' : ''}
                    </span>
                    <span style="flex:1;"></span>
                    <button type="button" class="cot-link" data-piezas-cerrar="${idx}">Cerrar</button>
                </div>
                <div class="cot-piezas">${chips}</div>
                <p style="margin:10px 0 0; color:var(--muted); font-size:12.5px;">
                    Si no eliges ninguna, el sistema toma las más antiguas.
                </p>
            </td>`;

        return tr;
    }

    function bindAutocomplete(inputEl, resultsEl, url, onPick, render) {
        let timer = null;
        const run = () => {
            const q = inputEl.value.trim();
            fetch(url + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    resultsEl.innerHTML = '';
                    if (!data.length) { resultsEl.classList.remove('open'); return; }
                    data.forEach(d => {
                        const el = document.createElement('div');
                        el.className = 'cot-ac-item';
                        el.innerHTML = render(d);
                        el.addEventListener('click', () => { onPick(d); resultsEl.classList.remove('open'); });
                        resultsEl.appendChild(el);
                    });
                    resultsEl.classList.add('open');
                });
        };
        inputEl.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(run, 220); });
        inputEl.addEventListener('focus', run);
        document.addEventListener('click', e => { if (!resultsEl.contains(e.target) && e.target !== inputEl) resultsEl.classList.remove('open'); });
    }

    function renderCliente() {
        const box = $('clienteSelected');
        if (!state.customer) { box.style.display = 'none'; return; }

        const c = state.customer;

        // Solo se dibuja el dato que existe: un renglón vacío estorba más
        // de lo que informa.
        const dato = (etiqueta, valor) => valor
            ? `<div class="cli-dato"><span class="e">${etiqueta}</span><span class="v">${valor}</span></div>`
            : '';

        const saldo = +c.saldo || 0;

        box.style.display = 'block';
        box.innerHTML = `
            <div class="cli-cab">
                <div style="min-width:0; flex:1;">
                    <b>${c.nombre}</b>
                    ${c.categoria ? `<span class="cli-cat">${c.categoria}</span>` : ''}
                    ${c.activo === false ? '<span class="cli-baja">Inactivo</span>' : ''}
                </div>
                <button type="button" id="clienteClear" class="cot-ico-btn" title="Quitar cliente" aria-label="Quitar cliente">${ICON_X}</button>
            </div>

            <div class="cli-datos">
                ${dato('Teléfono', c.telefono)}
                ${dato('Correo', c.correo)}
                ${dato('RFC', c.rfc)}
                ${dato('Dirección', c.direccion)}
                ${dato('Asesor', c.asesor)}
                ${dato('Conocido por', c.conocido)}
            </div>

            ${c.comentarios ? `<p class="cli-nota">${c.comentarios}</p>` : ''}

            <div class="cli-historial">
                <span><b>${c.compras || 0}</b> compra(s)</span>
                <span><b>${c.cotizaciones || 0}</b> cotización(es)</span>
                ${c.ultima_compra ? `<span>Última: <b>${c.ultima_compra}</b></span>` : ''}
            </div>

            ${saldo > 0
                ? `<p class="cli-debe">Debe <b>${money(saldo)}</b> de compras anteriores.</p>`
                : (c.compras ? '<p class="cli-alcorriente">Al corriente con sus pagos.</p>' : '')}`;

        $('clienteClear').addEventListener('click', () => { state.customer = null; $('clienteSearch').focus(); renderCliente(); });
    }
    bindAutocomplete($('clienteSearch'), $('clienteResults'), ROUTES.clientes,
        d => { state.customer = d; $('clienteSearch').value = ''; renderCliente(); },
        d => `<b>${d.nombre}</b><small>${[d.telefono, d.rfc, d.correo].filter(Boolean).join(' · ')}</small>`);

    function renderItems() {
        const body = $('itemsBody');
        body.innerHTML = '';
        if (!state.items.length) {
            body.innerHTML = '<tr id="itemsEmpty"><td colspan="10" style="text-align:center; color:var(--muted); padding:22px;">Busca y agrega productos arriba.</td></tr>';
            recalc(); return;
        }
        state.items.forEach((it, idx) => {
            const tr = document.createElement('tr');
            const sub = it.es_regalo ? 0 : (it.precio_unitario + it.sobreprecio) * it.cantidad;
            const thumb = it.imagen
                ? `<img class="cot-thumb" src="${it.imagen}" alt="">`
                : `<div class="cot-thumb"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>`;
            tr.innerHTML = `
                <td>${thumb}</td>
                <td style="font-weight:600;">${it.nombre}</td>
                <td>${it.modelo || '—'}</td>
                <td>${it.marca || '—'}</td>
                <td>${celdaStock(it, idx)}</td>
                <td><input type="number" min="1" class="cot-input cot-cell-num" data-k="cantidad" data-i="${idx}" value="${it.cantidad}"></td>
                <td>${it.es_regalo ? '<span style="color:var(--green); font-weight:700;">Regalo</span>' : money(sub)}</td>
                <td><input type="text" inputmode="decimal" class="cot-input cot-cell-num" data-k="sobreprecio" data-i="${idx}" value="${it.sobreprecio}"></td>
                <td><label class="cot-switch"><input type="checkbox" data-k="es_regalo" data-i="${idx}" ${it.es_regalo ? 'checked' : ''}><span class="track"></span></label></td>
                <td style="text-align:right;"><button type="button" class="cot-ico-btn danger" data-del="${idx}" title="Eliminar" aria-label="Eliminar">${ICON_TRASH}</button></td>`;
            body.appendChild(tr);

            // Renglón desplegable para elegir cuáles piezas se entregan.
            if (it.abierto && (it.piezas || []).length) {
                body.appendChild(filaDePiezas(it, idx));
            }
        });
        body.querySelectorAll('input[data-k]').forEach(inp => {
            inp.addEventListener('input', e => {
                const i = +e.target.dataset.i, k = e.target.dataset.k;
                if (k === 'es_regalo') state.items[i][k] = e.target.checked;
                else if (k === 'cantidad') state.items[i][k] = Math.max(1, parseInt(e.target.value) || 1);
                else state.items[i][k] = num(e.target.value);
                if (k === 'es_regalo' || k === 'cantidad') renderItems();
                else recalc();
            });
        });
        body.querySelectorAll('button[data-del]').forEach(b => b.addEventListener('click', () => { state.items.splice(+b.dataset.del, 1); renderItems(); }));

        // Abrir y cerrar el selector de piezas.
        body.querySelectorAll('button[data-piezas]').forEach(b => b.addEventListener('click', () => {
            const i = +b.dataset.piezas;
            state.items[i].abierto = !state.items[i].abierto;
            renderItems();
        }));
        body.querySelectorAll('button[data-piezas-cerrar]').forEach(b => b.addEventListener('click', () => {
            state.items[+b.dataset.piezasCerrar].abierto = false;
            renderItems();
        }));

        // Elegir o quitar una pieza concreta.
        body.querySelectorAll('input[data-pieza]').forEach(c => c.addEventListener('change', e => {
            const i = +e.target.dataset.pieza;
            const id = +e.target.value;
            const elegidas = state.items[i].seriales || [];

            state.items[i].seriales = e.target.checked
                ? [...elegidas, id]
                : elegidas.filter(x => x !== id);

            renderItems();
        }));

        recalc();
    }
    function addItem(d) {
        state.items.push({
            tipo_item: d.tipo_item, equipo_id: d.tipo_item === 'equipo' ? d.id : null,
            paquete_id: d.tipo_item === 'paquete' ? d.id : null,
            producto_id: d.tipo_item === 'producto' ? d.id : null,
            nombre: d.nombre, modelo: d.modelo, marca: d.marca, imagen: d.imagen,
            cantidad: 1, precio_unitario: +d.precio || 0, sobreprecio: 0, es_regalo: false,
            // Cuánto hay y cuáles piezas se pueden entregar.
            stock: +d.stock || 0, piezas: d.piezas || [], seriales: [], abierto: false,
        });
        renderItems();
        // El producto solo puede tener una ficha técnica: si la tiene, se adjunta sola.
        if (d.ficha && !state.fichas.some(f => f.id === d.ficha.id)) {
            state.fichas.push(d.ficha);
            renderFichas();
        }
    }
    bindAutocomplete($('prodSearch'), $('prodResults'), ROUTES.productos,
        d => { addItem(d); $('prodSearch').value = ''; },
        d => `<b>${d.nombre}</b> <span style="color:var(--muted);">${d.modelo || ''}</span><small>${d.marca || ''} · ${money(d.precio)}</small>`);

    function renderFichas() {
        const box = $('fichasSelected');
        box.innerHTML = '';
        state.fichas.forEach((f, idx) => {
            const chip = document.createElement('span');
            chip.className = 'cot-chip';
            chip.innerHTML = `${f.titulo} <button type="button" data-fi="${idx}">×</button>`;
            chip.querySelector('button').addEventListener('click', () => { state.fichas.splice(idx, 1); renderFichas(); });
            box.appendChild(chip);
        });
    }
    bindAutocomplete($('fichaSearch'), $('fichaResults'), ROUTES.fichas,
        d => { if (!state.fichas.some(f => f.id === d.id)) state.fichas.push(d); $('fichaSearch').value = ''; renderFichas(); },
        d => `${d.titulo}`);

    function calcDesglose() {
        let subtotal = 0;
        state.items.forEach(it => { if (!it.es_regalo) subtotal += (it.precio_unitario + it.sobreprecio) * it.cantidad; });
        subtotal = Math.round(subtotal * 100) / 100;
        const dTipo = $('descuento_tipo').value, dVal = num($('descuento_valor').value);
        let descuento = 0;
        if (dVal > 0) descuento = dTipo === 'porcentaje' ? subtotal * Math.min(dVal, 100) / 100 : Math.min(dVal, subtotal);
        descuento = Math.round(descuento * 100) / 100;
        const envio = num($('envio').value);
        const base = Math.max(0, subtotal - descuento) + envio;
        const iva = $('aplica_iva').checked ? Math.round(base * IVA * 100) / 100 : 0;
        const total = Math.round((base + iva) * 100) / 100;
        const valorACuenta = num($('valor_a_cuenta').value);
        const contrato = Math.round(Math.max(0, total - valorACuenta) * 100) / 100;
        return { subtotal, descuento, iva, total, contrato };
    }
    function recalc() {
        const d = calcDesglose();
        $('tSubtotal').textContent = money(d.subtotal);
        $('tDescuento').textContent = '-' + money(d.descuento);
        $('tIva').textContent = money(d.iva);
        $('tTotal').textContent = money(d.total);
        $('tContrato').textContent = money(d.contrato);
        if ($('modalidad').value === 'financiamiento') renderPagos();
    }
    ['envio', 'descuento_valor', 'valor_a_cuenta'].forEach(id => $(id).addEventListener('input', recalc));
    $('descuento_tipo').addEventListener('change', recalc);
    $('aplica_iva').addEventListener('change', recalc);

    function addMonths(dateStr, m) {
        const base = dateStr ? new Date(dateStr + 'T00:00:00') : new Date();
        const d = new Date(base.getFullYear(), base.getMonth() + m, base.getDate());
        return d.toISOString().slice(0, 10);
    }
    function addDias(dateStr, dias) {
        const base = dateStr ? new Date(dateStr + 'T00:00:00') : new Date();
        const d = new Date(base.getFullYear(), base.getMonth(), base.getDate() + dias);
        return d.toISOString().slice(0, 10);
    }
    function nombrePago(i) {
        if (i === 0) return 'Pago inicial';
        const ord = ['', 'Primer', 'Segundo', 'Tercer', 'Cuarto', 'Quinto', 'Sexto', 'Séptimo', 'Octavo', 'Noveno', 'Décimo', 'Décimo primer', 'Décimo segundo'];
        return (ord[i] || (i + 'º')) + ' pago';
    }
    function rebuildPagos() {
        const meses = Math.max(1, parseInt(state.meses) || 1);
        const hoy = new Date().toISOString().slice(0, 10);
        const prev = state.pagos;

        // En automático, cuántos pagos salen depende de la frecuencia: cada
        // "frecuenciaDias" días, dentro del plazo en meses (30 días = 1 mes).
        const frecuencia = Math.max(1, parseInt(state.frecuenciaDias) || 30);
        const count = state.modoPagos === 'automatico'
            ? Math.max(1, Math.round((meses * 30) / frecuencia)) + 1
            : Math.max(1, prev.length || meses + 1);

        state.pagos = [];
        for (let i = 0; i < count; i++) {
            const p = prev[i];
            state.pagos.push({
                nombre: p ? p.nombre : nombrePago(i),
                fecha: p ? p.fecha : addDias(hoy, i * frecuencia),
                monto: p ? +p.monto : 0,
                porcentaje: p ? +p.porcentaje : 0,
                bloqueado: p ? !!p.bloqueado : false,
            });
        }
        distribuir();
    }
    function distribuir() {
        const contrato = calcDesglose().contrato;
        let sumBloq = 0; const libres = [];
        state.pagos.forEach((p, i) => { if (p.bloqueado) sumBloq += p.monto; else libres.push(i); });
        const restante = Math.round((contrato - sumBloq) * 100) / 100;
        if (libres.length) {
            const cuota = Math.floor((restante / libres.length) * 100) / 100;
            libres.forEach((idx, pos) => {
                state.pagos[idx].monto = pos === libres.length - 1
                    ? Math.round((restante - cuota * (libres.length - 1)) * 100) / 100
                    : cuota;
                if (state.pagos[idx].monto < 0) state.pagos[idx].monto = 0;
            });
        }
        state.pagos.forEach(p => { p.porcentaje = contrato > 0 ? Math.round(p.monto / contrato * 10000) / 100 : 0; });
    }
    function addPago() {
        const frecuencia = Math.max(1, parseInt(state.frecuenciaDias) || 30);
        const last = state.pagos[state.pagos.length - 1];
        const fecha = last ? addDias(last.fecha, frecuencia) : new Date().toISOString().slice(0, 10);
        state.pagos.push({ nombre: nombrePago(state.pagos.length), fecha, monto: 0, porcentaje: 0, bloqueado: false });
        distribuir(); renderPagos();
    }
    function renderPagos() {
        const wrap = $('pagosBlock');
        const contrato = calcDesglose().contrato;
        const suma = Math.round(state.pagos.reduce((a, p) => a + (+p.monto || 0), 0) * 100) / 100;
        const cuadra = Math.abs(suma - contrato) < 0.5;

        const esAutomatico = state.modoPagos === 'automatico';
        const frecuenciasFijas = [15, 30];
        const esFrecuenciaFija = frecuenciasFijas.includes(+state.frecuenciaDias);

        let html = `<div class="cot-plan">
            <div class="cot-plan-head" style="flex-wrap:wrap; gap:10px;">
                <b>Plan de pagos</b>
                <div class="cot-seg" role="tablist" style="height:32px;">
                    <button type="button" class="cot-seg-btn ${esAutomatico ? 'on' : ''}" data-modo-pagos="automatico" style="padding:0 14px; font-size:12.5px;">Automático</button>
                    <button type="button" class="cot-seg-btn ${!esAutomatico ? 'on' : ''}" data-modo-pagos="manual" style="padding:0 14px; font-size:12.5px;">Manual</button>
                </div>
            </div>`;

        if (esAutomatico) {
            html += `
            <div class="cot-plan-head" style="border-top:0; padding-top:0;">
                <div style="display:flex; align-items:center; gap:9px;">
                    <span class="cot-lbl" style="margin:0;">Diferir a</span>
                    <div class="cot-step">
                        <button type="button" id="mesMinus" aria-label="Menos meses">−</button>
                        <span class="val">${state.meses}</span>
                        <button type="button" id="mesPlus" aria-label="Más meses">+</button>
                    </div>
                    <span class="cot-lbl" style="margin:0;">meses</span>
                </div>
                <div style="display:flex; align-items:center; gap:9px;">
                    <span class="cot-lbl" style="margin:0;">Cobrar cada</span>
                    <select id="frecuenciaSelect" class="cot-input" style="width:auto; padding:6px 10px;">
                        <option value="15" ${+state.frecuenciaDias === 15 ? 'selected' : ''}>15 días</option>
                        <option value="30" ${+state.frecuenciaDias === 30 ? 'selected' : ''}>30 días</option>
                        <option value="personalizado" ${!esFrecuenciaFija ? 'selected' : ''}>Personalizado</option>
                    </select>
                    ${!esFrecuenciaFija ? `<input type="number" min="1" id="frecuenciaPersonalizada" class="cot-input" style="width:70px; padding:6px 8px;" value="${state.frecuenciaDias}"><span class="cot-lbl" style="margin:0;">días</span>` : ''}
                </div>
            </div>`;
        }

        state.pagos.forEach((p, idx) => {
            html += `
                <div class="cot-prow">
                    <span class="cot-prow-num">${idx + 1}</span>
                    <div class="cot-prow-info">
                        <div class="cot-prow-name">${p.nombre}</div>
                        ${esAutomatico
                            ? `<div class="cot-prow-date">${fmtFecha(p.fecha)}</div>`
                            : `<input type="date" data-fecha="${idx}" value="${p.fecha}" class="cot-input" style="width:auto; padding:5px 8px; font-size:12.5px; margin-top:4px;">`}
                    </div>
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <button type="button" class="cot-lock ${p.bloqueado ? 'on' : ''}" data-lock="${idx}" title="Fijo: conserva su monto. Auto: se reparte solo.">${p.bloqueado ? ICON_LOCK : ICON_UNLOCK}${p.bloqueado ? 'Fijo' : 'Auto'}</button>
                        <div class="cot-pay-mini"><input type="text" inputmode="decimal" data-pct="${idx}" value="${p.porcentaje}"><span>%</span></div>
                        <div class="cot-pay-mini"><input type="text" inputmode="decimal" data-monto="${idx}" value="${p.monto}" style="width:100px;"><span>MXN</span></div>
                        <button type="button" class="cot-ico-btn danger" data-delp="${idx}" title="Eliminar" aria-label="Eliminar">${ICON_TRASH}</button>
                    </div>
                </div>`;
        });

        html += `
            <div class="cot-plan-foot">
                <button type="button" class="cot-add" id="addPagoBtn"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Agregar pago</button>
                <div class="cot-pay-sum ${cuadra ? 'ok' : 'bad'}"><span>${cuadra ? '✓ Cuadra con el contrato' : '⚠ No cuadra'}</span><span>${money(suma)} / ${money(contrato)}</span></div>
            </div>
        </div>`;

        wrap.innerHTML = html;

        wrap.querySelectorAll('button[data-modo-pagos]').forEach(b => b.addEventListener('click', () => {
            state.modoPagos = b.dataset.modoPagos;
            if (state.modoPagos === 'automatico') rebuildPagos();
            renderPagos();
        }));
        if (esAutomatico) {
            $('mesMinus').addEventListener('click', () => { state.meses = Math.max(1, state.meses - 1); rebuildPagos(); renderPagos(); });
            $('mesPlus').addEventListener('click', () => { state.meses = Math.min(60, state.meses + 1); rebuildPagos(); renderPagos(); });
            $('frecuenciaSelect').addEventListener('change', e => {
                state.frecuenciaDias = e.target.value === 'personalizado' ? 45 : parseInt(e.target.value);
                rebuildPagos(); renderPagos();
            });
            const personalizada = document.getElementById('frecuenciaPersonalizada');
            if (personalizada) personalizada.addEventListener('change', e => {
                state.frecuenciaDias = Math.max(1, parseInt(e.target.value) || 30);
                rebuildPagos(); renderPagos();
            });
        }
        wrap.querySelectorAll('input[data-fecha]').forEach(c => c.addEventListener('change', e => {
            const i = +e.target.dataset.fecha;
            state.pagos[i].fecha = e.target.value;
        }));
        $('addPagoBtn').addEventListener('click', addPago);
        wrap.querySelectorAll('button[data-lock]').forEach(b => b.addEventListener('click', () => {
            const i = +b.dataset.lock; state.pagos[i].bloqueado = !state.pagos[i].bloqueado; distribuir(); renderPagos();
        }));
        wrap.querySelectorAll('input[data-pct]').forEach(c => c.addEventListener('change', e => {
            const i = +e.target.dataset.pct;
            state.pagos[i].monto = Math.round(contrato * num(e.target.value) / 100 * 100) / 100;
            state.pagos[i].bloqueado = true; distribuir(); renderPagos();
        }));
        wrap.querySelectorAll('input[data-monto]').forEach(c => c.addEventListener('change', e => {
            const i = +e.target.dataset.monto;
            state.pagos[i].monto = num(e.target.value); state.pagos[i].bloqueado = true; distribuir(); renderPagos();
        }));
        wrap.querySelectorAll('button[data-delp]').forEach(b => b.addEventListener('click', () => {
            state.pagos.splice(+b.dataset.delp, 1); distribuir(); renderPagos();
        }));
    }
    function toggleModalidad() {
        const val = $('modalidad').value;
        const fin = val === 'financiamiento';
        document.querySelectorAll('.cot-seg-btn').forEach(b => b.classList.toggle('on', b.dataset.mod === val));
        $('pagosBlock').style.display = fin ? 'block' : 'none';
        if (fin) { if (!state.pagos.length) rebuildPagos(); else distribuir(); renderPagos(); }
    }
    document.querySelectorAll('.cot-seg-btn').forEach(b => b.addEventListener('click', () => {
        $('modalidad').value = b.dataset.mod; toggleModalidad();
    }));

    $('cotForm').addEventListener('submit', function (e) {
        if (!state.customer) { e.preventDefault(); showToast('Selecciona un cliente.', 'warn'); return; }
        if (!state.items.length) { e.preventDefault(); showToast('Agrega al menos un producto.', 'warn'); return; }

        const box = $('hiddenInputs'); box.innerHTML = '';
        const add = (name, val) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = name; i.value = val ?? ''; box.appendChild(i); };

        add('customer_id', state.customer.id);
        add('congreso_id', $('congreso_id').value);
        add('garantia_meses', $('garantia_meses').value);
        add('nota_cliente', $('nota_cliente').value);
        add('modalidad', $('modalidad').value);
        add('aplica_iva', $('aplica_iva').checked ? 1 : 0);
        add('descuento_tipo', $('descuento_tipo').value);
        add('descuento_valor', num($('descuento_valor').value));
        add('envio', num($('envio').value));
        add('valor_a_cuenta', num($('valor_a_cuenta').value));
        add('num_meses', $('modalidad').value === 'financiamiento' ? (parseInt(state.meses) || 0) : 0);

        state.items.forEach((it, i) => {
            add(`items[${i}][tipo_item]`, it.tipo_item);
            add(`items[${i}][equipo_id]`, it.equipo_id ?? '');
            add(`items[${i}][paquete_id]`, it.paquete_id ?? '');
            add(`items[${i}][producto_id]`, it.producto_id ?? '');
            add(`items[${i}][nombre]`, it.nombre);
            add(`items[${i}][modelo]`, it.modelo ?? '');
            add(`items[${i}][marca]`, it.marca ?? '');
            add(`items[${i}][imagen]`, it.imagen ?? '');
            add(`items[${i}][cantidad]`, it.cantidad);
            add(`items[${i}][precio_unitario]`, it.precio_unitario);
            add(`items[${i}][sobreprecio]`, it.sobreprecio);
            add(`items[${i}][es_regalo]`, it.es_regalo ? 1 : 0);

            // Las piezas que se eligieron a mano. Si no se eligió ninguna,
            // el servidor toma las más antiguas.
            (it.seriales || []).forEach(id => add(`items[${i}][seriales][]`, id));
        });

        if ($('modalidad').value === 'financiamiento') {
            state.pagos.forEach((p, i) => {
                add(`pagos[${i}][nombre]`, p.nombre);
                add(`pagos[${i}][fecha]`, p.fecha ?? '');
                add(`pagos[${i}][porcentaje]`, p.porcentaje);
                add(`pagos[${i}][monto]`, p.monto);
                add(`pagos[${i}][bloqueado]`, p.bloqueado ? 1 : 0);
            });
        }
        state.fichas.forEach((f, i) => add(`fichas[${i}]`, f.id));
    });

    // Init
    $('congreso_id').value = INITIAL.congreso_id || '';
    $('garantia_meses').value = INITIAL.garantia_meses || 6;
    $('nota_cliente').value = INITIAL.nota_cliente || '';
    $('modalidad').value = INITIAL.modalidad || 'contado';
    $('aplica_iva').checked = !!INITIAL.aplica_iva;
    $('envio').value = INITIAL.envio || 0;
    $('descuento_tipo').value = INITIAL.descuento_tipo || 'porcentaje';
    $('descuento_valor').value = INITIAL.descuento_valor || 0;
    $('valor_a_cuenta').value = INITIAL.valor_a_cuenta || 0;
    renderCliente(); renderItems(); renderFichas(); toggleModalidad();
})();
</script>
