@extends('structure.commercial_management.erp')

@section('title', 'Orden de servicio · ' . ($service->service_number ?? 'OS-' . $service->id))

@section('erp_content')
    @php
        $accionesBase = [
            'Diagnóstico general',
            'Limpieza interna y externa',
            'Lubricación de componentes',
            'Ajuste y calibración',
            'Cambio de refacciones / piezas',
            'Reparación de componentes',
            'Pruebas de funcionamiento',
        ];
        $inputStyle = 'width:100%; padding:11px 13px; border:1px solid var(--border); border-radius:9px; background:var(--surface); color:var(--text); font-size:14px;';
        $labelStyle = 'display:block; font-size:12px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.03em; margin-bottom:6px;';
    @endphp

    <div class="erp-head">
        <div class="erp-head-l">
            <a href="{{ route('gestion.servicios.servicio_externo.orden_servicio', $service) }}" class="erp-back" title="Volver">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            </a>
            <h1 class="erp-h1">Orden de servicio · {{ $service->service_number ?? ('OS-' . $service->id) }}</h1>
            <span class="erp-badge info"><span class="dot"></span>Externo interno</span>
        </div>
    </div>

    <div style="margin-bottom:14px; font-size:12px; color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.04em;">Paso 3 de 4 · Mantenimiento realizado</div>

    <form method="POST" action="{{ route('gestion.servicios.servicio_externo.orden_servicio.paso4', $service) }}" id="os-form">
        @csrf
        @foreach (['fecha_ingreso', 'fecha_mantenimiento', 'equipo', 'marca', 'modelo', 'serie', 'observaciones', 'proximo_mantenimiento', 'tipo_mantenimiento'] as $campo)
            <input type="hidden" name="{{ $campo }}" value="{{ $datos[$campo] ?? '' }}">
        @endforeach

        <div class="erp-card pad" style="max-width:720px;">
            <div style="{{ $labelStyle }} margin-bottom:12px;">Mantenimiento realizado</div>

            <div id="acciones-list">
                @foreach ($accionesBase as $accion)
                    <div class="accion-item" style="display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid var(--border); border-radius:9px; margin-bottom:8px;">
                        <input type="checkbox" name="acciones[]" value="{{ $accion }}" {{ $accionesPrevias->contains($accion) ? 'checked' : '' }} style="width:17px; height:17px; accent-color:var(--primary);">
                        <span style="flex:1; font-size:14px;">{{ $accion }}</span>
                        <button type="button" class="accion-del" style="width:22px; height:22px; border:none; border-radius:50%; background:rgba(220,38,38,.12); color:var(--danger); font-size:12px; cursor:pointer; line-height:1;">✕</button>
                    </div>
                @endforeach
                @foreach ($accionesPrevias->diff($accionesBase) as $extra)
                    <div class="accion-item" style="display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid var(--border); border-radius:9px; margin-bottom:8px;">
                        <input type="checkbox" name="acciones[]" value="{{ $extra }}" checked style="width:17px; height:17px; accent-color:var(--primary);">
                        <span style="flex:1; font-size:14px;">{{ $extra }}</span>
                        <button type="button" class="accion-del" style="width:22px; height:22px; border:none; border-radius:50%; background:rgba(220,38,38,.12); color:var(--danger); font-size:12px; cursor:pointer; line-height:1;">✕</button>
                    </div>
                @endforeach
            </div>

            <div style="margin-top:16px;">
                <label for="accion-manual" style="{{ $labelStyle }}">Agregar acción manual</label>
                <input type="text" id="accion-manual" placeholder="Escribe y presiona Enter..." style="{{ $inputStyle }} max-width:420px;">
            </div>
        </div>

        <div style="margin-top:18px; display:flex; gap:10px; align-items:center;">
            <button type="button" class="erp-btn ghost" onclick="history.back()">← Anterior</button>
            <button type="submit" class="erp-btn">Siguiente →</button>
        </div>
    </form>

    <script>
        document.getElementById('accion-manual').addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            var texto = this.value.trim();
            if (!texto) return;
            agregarAccion(texto);
            this.value = '';
        });

        function agregarAccion(texto) {
            var item = document.createElement('div');
            item.className = 'accion-item';
            item.style.cssText = 'display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid var(--border); border-radius:9px; margin-bottom:8px;';
            item.innerHTML = '<input type="checkbox" name="acciones[]" checked style="width:17px; height:17px; accent-color:var(--primary);">'
                + '<span style="flex:1; font-size:14px;"></span>'
                + '<button type="button" class="accion-del" style="width:22px; height:22px; border:none; border-radius:50%; background:rgba(220,38,38,.12); color:var(--danger); font-size:12px; cursor:pointer; line-height:1;">✕</button>';
            item.querySelector('input').value = texto;
            item.querySelector('span').textContent = texto;
            document.getElementById('acciones-list').appendChild(item);
        }

        document.getElementById('acciones-list').addEventListener('click', function (e) {
            if (e.target.classList.contains('accion-del')) {
                e.target.closest('.accion-item').remove();
            }
        });
    </script>
@endsection
