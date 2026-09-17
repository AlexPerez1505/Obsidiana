@extends('structure.commercial_management.erp')

@section('title', 'Historial de Servicios')

@section('erp_content')
    @php
        $total = $services->count();
        $registrados = $services->where('status', 'registrado')->count();
        $enProgreso = $services->where('status', 'en_progreso')->count();
        $entregados = $services->where('status', 'entregado')->count();
        $cancelados = $services->where('status', 'cancelado')->count();

        $tiposEquipo = $services->pluck('serviceEquipment.type_text')->filter()->unique()->sort()->values();
        $estados = $services->pluck('status')->filter()->unique()->sort()->values();

        $filas = $services->map(function ($service) {
            $customerName = trim(($service->customer?->nombre ?? '') . ' ' . ($service->customer?->apellido ?? '')) ?: 'Sin cliente';
            $tipoEquipo = $service->serviceEquipment?->type_text ?: '—';
            return [
                'modelo' => $service,
                'os' => $service->service_number ?? ('OS-' . $service->id),
                'cliente' => $customerName,
                'tipo' => $service->service_type ?? '—',
                'estado' => $service->status ?? 'registrado',

                'fecha' => $service->created_at?->format('Y-m-d') ?? '',
                'fechaH' => $service->created_at?->format('d/m/Y') ?? '—',
                'equipo' => $tipoEquipo,
                'marca' => $service->serviceEquipment?->brand_text ?? '—',
                'modeloEquipo' => $service->serviceEquipment?->model_text ?? '—',
            ];
        });
    @endphp

    <style>
        .hs-head { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:20px; }
        .hs-head h1 { font-size:20px; font-weight:600; margin:0; }

        .hs-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:16px; margin-bottom:24px; }
        .hs-stat { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:20px; display:flex; align-items:center; gap:14px; }
        .hs-stat .ic { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex:0 0 auto; }
        .hs-stat .ic svg { width:24px; height:24px; }
        .hs-stat .ic.blue { background:var(--primary-soft); color:var(--primary); }
        .hs-stat .ic.amber { background:var(--accent-soft); color:var(--accent); }
        .hs-stat .ic.green { background:var(--green-soft); color:var(--green); }
        .hs-stat .ic.slate { background:var(--surface-2); color:var(--muted); }
        .hs-stat .ic.danger { background:var(--danger-soft); color:var(--danger); }
        .hs-stat .n { font-size:26px; font-weight:800; line-height:1; }
        .hs-stat .l { color:var(--muted); font-size:13px; margin-top:3px; }

        .hs-toolbar { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
        .hs-search { position:relative; flex:1; min-width:260px; }
        .hs-search svg { position:absolute; left:13px; top:50%; transform:translateY(-50%); width:17px; height:17px; color:var(--muted); }
        .hs-search input { width:100%; padding:11px 14px 11px 40px; border:1px solid var(--border); border-radius:12px; font-size:14px; background:var(--surface); color:var(--text); outline:none; }
        .hs-search input:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(0,122,255,.1); }

        .hs-filter { position:relative; }
        .hs-filter-btn { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border:1px solid var(--border); border-radius:10px; background:var(--surface); color:var(--text); font-size:13.5px; font-weight:500; cursor:pointer; }
        .hs-filter-btn:hover { background:var(--surface-2); }
        .hs-filter-btn .count { background:var(--primary); color:#fff; border-radius:999px; padding:1px 7px; font-size:11px; font-weight:700; }
        .hs-filter-panel { position:absolute; top:calc(100% + 8px); right:0; min-width:220px; background:var(--surface); border:1px solid var(--border); border-radius:12px; box-shadow:0 18px 44px rgba(17,24,39,.18); padding:14px; z-index:50; display:none; }
        .hs-filter-panel.open { display:block; }
        .hs-filter-group { margin-bottom:14px; }
        .hs-filter-group:last-child { margin-bottom:0; }
        .hs-filter-group h4 { font-size:12px; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; margin:0 0 10px; }
        .hs-filter-opt { display:flex; align-items:center; gap:8px; padding:6px 0; cursor:pointer; font-size:13.5px; }
        .hs-filter-opt input { accent-color:var(--primary); }
        .hs-filter-dates { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
        .hs-filter-dates input { width:100%; padding:8px 10px; border:1px solid var(--border); border-radius:8px; background:var(--surface); color:var(--text); font-size:13px; }

        .hs-clear { width:36px; height:36px; border:1px solid var(--border); border-radius:10px; background:var(--surface); color:var(--muted); display:inline-flex; align-items:center; justify-content:center; cursor:pointer; }
        .hs-clear:hover { background:var(--surface-2); color:var(--text); }

        .hs-chips { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
        .hs-chip { display:inline-flex; align-items:center; gap:6px; padding:5px 12px; border-radius:999px; background:var(--surface-2); border:1px solid var(--border); font-size:12px; color:var(--text); }
        .hs-chip button { background:none; border:none; color:var(--muted); cursor:pointer; font-size:16px; line-height:1; padding:0; }

        .hs-table { width:100%; border-collapse:collapse; font-size:13.5px; }
        .hs-table th { text-align:left; padding:12px 16px; color:var(--muted); font-weight:500; font-size:13px; border-bottom:1px solid var(--border); white-space:nowrap; }
        .hs-table td { padding:12px 16px; border-bottom:1px solid var(--border); vertical-align:middle; }
        .hs-table tbody tr:last-child td { border-bottom:none; }
        .hs-table tbody tr:hover td { background:var(--surface-2); }

        .hs-badge { display:inline-flex; align-items:center; gap:6px; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:600; }
        .hs-badge .dot { width:6px; height:6px; border-radius:50%; background:currentColor; }
        .hs-badge.ok { background:var(--green-soft); color:var(--green); }
        .hs-badge.info { background:var(--primary-soft); color:var(--primary); }
        .hs-badge.warn { background:var(--accent-soft); color:var(--accent); }
        .hs-badge.danger { background:var(--danger-soft); color:var(--danger); }
        .hs-badge.neutral { background:var(--surface-2); color:var(--muted); }

        .hs-foot { display:flex; align-items:center; justify-content:space-between; color:var(--muted); font-size:13px; margin-top:14px; }

        .hs-empty { text-align:center; padding:40px 16px; color:var(--muted); }
    </style>

    <div class="hs-head">
        <h1>Historial de Servicios</h1>
        <div style="display:flex; align-items:center; gap:10px;">
            <a href="{{ route('gestion.servicios.historial.aprobaciones.index') }}" class="erp-btn ghost">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                Aprobaciones
                @if ($registrados > 0)
                    <span style="background:var(--accent); color:#fff; border-radius:999px; padding:1px 8px; font-size:11px; font-weight:700;">{{ $registrados }}</span>
                @endif
            </a>
            <a href="{{ route('gestion.servicios.registro') }}" class="erp-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nuevo servicio
            </a>
        </div>
    </div>

    {{-- Estadísticas --}}
    <div class="hs-stats">
        <div class="hs-stat">
            <span class="ic blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>
            <div><div class="n">{{ $total }}</div><div class="l">Total</div></div>
        </div>
        <div class="hs-stat">
            <span class="ic amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></span>
            <div><div class="n">{{ $registrados }}</div><div class="l">Registrados</div></div>
        </div>
        <div class="hs-stat">
            <span class="ic slate"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
            <div><div class="n">{{ $enProgreso }}</div><div class="l">En progreso</div></div>
        </div>
        <div class="hs-stat">
            <span class="ic green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="20 6 9 17 4 12"/></svg></span>
            <div><div class="n">{{ $entregados }}</div><div class="l">Entregados</div></div>
        </div>
        <div class="hs-stat">
            <span class="ic danger"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></span>
            <div><div class="n">{{ $cancelados }}</div><div class="l">Cancelados</div></div>
        </div>
    </div>

    {{-- Barra de búsqueda y filtros --}}
    <div class="hs-toolbar">
        <div class="hs-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="hsBuscar" placeholder="Buscar por OS, cliente o tipo de equipo" autocomplete="off">
        </div>

        <div class="hs-filter" data-hs-filter>
            <button type="button" class="hs-filter-btn" data-hs-filter-toggle>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Filtros
                <span class="count" data-hs-filter-count hidden>0</span>
            </button>
            <div class="hs-filter-panel" data-hs-filter-panel hidden>
                <div class="hs-filter-group">
                    <h4>Estado</h4>
                    @foreach ($estados as $estado)
                        <label class="hs-filter-opt">
                            <input type="checkbox" data-hs-f="estado" value="{{ $estado }}">
                            <span>{{ ucfirst(str_replace('_', ' ', $estado)) }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="hs-filter-group">
                    <h4>Tipo de equipo</h4>
                    @foreach ($tiposEquipo as $tipo)
                        <label class="hs-filter-opt">
                            <input type="checkbox" data-hs-f="equipo" value="{{ $tipo }}">
                            <span>{{ $tipo }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="hs-filter-group">
                    <h4>Rango de fechas</h4>
                    <div class="hs-filter-dates">
                        <input type="date" data-hs-f="desde" aria-label="Desde">
                        <input type="date" data-hs-f="hasta" aria-label="Hasta">
                    </div>
                </div>
            </div>
        </div>

        <button type="button" class="hs-clear" id="hsLimpiar" title="Limpiar filtros">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        </button>
    </div>

    <div class="hs-chips" id="hsChips" hidden></div>

    {{-- Tabla --}}
    <div class="erp-card" style="overflow:hidden;">
        <table class="hs-table">
            <thead>
                <tr>
                    <th>OS</th>
                    <th>Cliente</th>
                    <th>Tipo</th>
                    <th>Equipo</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    @php
                        $badge = match($fila['estado']) {
                            'entregado' => 'ok',
                            'en_progreso' => 'info',
                            'cancelado' => 'danger',
                            'registrado' => 'warn',
                            default => 'neutral',
                        };
                    @endphp
                    <tr class="hs-row"
                        data-hs-buscar="{{ mb_strtolower($fila['os'] . ' ' . $fila['cliente'] . ' ' . $fila['equipo'] . ' ' . $fila['marca'] . ' ' . $fila['modeloEquipo']) }}"
                        data-hs-estado="{{ $fila['estado'] }}"
                        data-hs-equipo="{{ $fila['equipo'] }}"
                        data-hs-fecha="{{ $fila['fecha'] }}">
                        <td class="erp-strong">{{ $fila['os'] }}</td>
                        <td>{{ $fila['cliente'] }}</td>
                        <td style="text-transform:capitalize;">{{ $fila['tipo'] }}</td>
                        <td>{{ $fila['equipo'] }}</td>
                        <td><span class="hs-badge {{ $badge }}"><span class="dot"></span>{{ ucfirst(str_replace('_', ' ', $fila['estado'])) }}</span></td>
                        <td style="color:var(--muted);">{{ $fila['fechaH'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="hs-empty">
                                <h3>Aún no hay servicios</h3>
                                <p>Crea el primero y aparecerá en esta lista.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="hs-foot" id="hsFoot" hidden>
            <span id="hsTotalText"></span>
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        const buscar = document.getElementById('hsBuscar');
        const filas = document.querySelectorAll('.hs-row');
        const filtros = document.querySelector('[data-hs-filter]');
        const toggle = document.querySelector('[data-hs-filter-toggle]');
        const panel = document.querySelector('[data-hs-filter-panel]');
        const countBadge = document.querySelector('[data-hs-filter-count]');
        const chips = document.getElementById('hsChips');
        const limpiar = document.getElementById('hsLimpiar');
        const foot = document.getElementById('hsFoot');
        const totalText = document.getElementById('hsTotalText');

        let estado = [];
        let equipo = [];
        let desde = '';
        let hasta = '';

        function normalizar(s) { return (s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim(); }

        function aplicar() {
            const q = normalizar(buscar.value);
            let visibles = 0;

            filas.forEach(function (row) {
                const texto = normalizar(row.getAttribute('data-hs-buscar'));
                const rowEstado = row.getAttribute('data-hs-estado');
                const rowEquipo = row.getAttribute('data-hs-equipo');
                const rowFecha = row.getAttribute('data-hs-fecha');

                const okBuscar = q === '' || texto.includes(q);
                const okEstado = estado.length === 0 || estado.includes(rowEstado);
                const okEquipo = equipo.length === 0 || equipo.includes(rowEquipo);

                let okFecha = true;
                if (desde && rowFecha && rowFecha < desde) okFecha = false;
                if (hasta && rowFecha && rowFecha > hasta) okFecha = false;

                const mostrar = okBuscar && okEstado && okEquipo && okFecha;
                row.style.display = mostrar ? '' : 'none';
                if (mostrar) visibles++;
            });

            totalText.textContent = 'Mostrando ' + visibles + ' servicios';
            foot.hidden = false;
            actualizarChips();
            actualizarContador();
        }

        function actualizarContador() {
            const n = estado.length + equipo.length + (desde ? 1 : 0) + (hasta ? 1 : 0);
            countBadge.textContent = n;
            countBadge.hidden = n === 0;
            chips.hidden = n === 0;
        }

        function actualizarChips() {
            chips.innerHTML = '';
            estado.forEach(function (v) {
                const chip = document.createElement('span');
                chip.className = 'hs-chip';
                chip.innerHTML = 'Estado: ' + v.replace(/_/g, ' ') + ' <button data-remove="estado" data-val="' + v + '">&times;</button>';
                chips.appendChild(chip);
            });
            equipo.forEach(function (v) {
                const chip = document.createElement('span');
                chip.className = 'hs-chip';
                chip.innerHTML = 'Equipo: ' + v + ' <button data-remove="equipo" data-val="' + v + '">&times;</button>';
                chips.appendChild(chip);
            });
            if (desde) {
                const chip = document.createElement('span');
                chip.className = 'hs-chip';
                chip.innerHTML = 'Desde: ' + desde + ' <button data-remove="desde">&times;</button>';
                chips.appendChild(chip);
            }
            if (hasta) {
                const chip = document.createElement('span');
                chip.className = 'hs-chip';
                chip.innerHTML = 'Hasta: ' + hasta + ' <button data-remove="hasta">&times;</button>';
                chips.appendChild(chip);
            }
        }

        function leerFiltros() {
            estado = [];
            equipo = [];
            document.querySelectorAll('[data-hs-f="estado"]:checked').forEach(function (el) { estado.push(el.value); });
            document.querySelectorAll('[data-hs-f="equipo"]:checked').forEach(function (el) { equipo.push(el.value); });
            desde = document.querySelector('[data-hs-f="desde"]').value;
            hasta = document.querySelector('[data-hs-f="hasta"]').value;
        }

        buscar?.addEventListener('input', aplicar);

        toggle?.addEventListener('click', function (e) {
            e.stopPropagation();
            panel.hidden = !panel.hidden;
            panel.classList.toggle('open', !panel.hidden);
        });

        document.addEventListener('click', function (e) {
            if (!filtros.contains(e.target)) {
                panel.hidden = true;
                panel.classList.remove('open');
            }
        });

        filtros?.addEventListener('change', function (e) {
            if (e.target.matches('[data-hs-f]')) {
                leerFiltros();
                aplicar();
            }
        });

        chips?.addEventListener('click', function (e) {
            const btn = e.target.closest('button[data-remove]');
            if (!btn) return;
            const tipo = btn.getAttribute('data-remove');
            const val = btn.getAttribute('data-val');

            if (tipo === 'estado') {
                estado = estado.filter(function (x) { return x !== val; });
                document.querySelector('[data-hs-f="estado"][value="' + val + '"]').checked = false;
            } else if (tipo === 'equipo') {
                equipo = equipo.filter(function (x) { return x !== val; });
                document.querySelector('[data-hs-f="equipo"][value="' + val + '"]').checked = false;
            } else if (tipo === 'desde') {
                desde = '';
                document.querySelector('[data-hs-f="desde"]').value = '';
            } else if (tipo === 'hasta') {
                hasta = '';
                document.querySelector('[data-hs-f="hasta"]').value = '';
            }
            aplicar();
        });

        limpiar?.addEventListener('click', function () {
            buscar.value = '';
            document.querySelectorAll('[data-hs-f]').forEach(function (el) { el.checked = false; el.value = ''; });
            estado = [];
            equipo = [];
            desde = '';
            hasta = '';
            aplicar();
        });

        aplicar();
    })();
    </script>
    @endpush
@endsection
