@extends('structure.gestion_servicios.layout')

@section('title', 'Cotización')

@push('head')
<style>
.area-header { display:flex; align-items:flex-start; justify-content:space-between; gap:24px; flex-wrap:wrap; margin-bottom:32px; }
.area-title { font-size:24px; margin:0; }
.area-subtitle { font-size:13px; color:var(--muted); margin:6px 0 0; }
.cotizacion-field { padding:12px 16px; border:1px solid var(--border); border-radius:10px; background:var(--surface); }
.cotizacion-field label { font-size:11px; color:var(--muted); font-weight:700; margin-bottom:8px; display:block; text-transform:uppercase; letter-spacing:.04em; }
.cotizacion-field .value { font-size:15px; color:var(--text); line-height:1.5; }
.search-box { position:relative; margin:28px 0 22px; }
.search-box input { width:100%; padding:14px 16px 14px 44px; border:1px solid var(--border); border-radius:10px; background:var(--surface); color:var(--text); font-size:14px; }
.search-box svg { position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--muted); }
.services-table { width:100%; border-collapse:collapse; font-size:14px; margin-bottom:12px; }
.services-table th, .services-table td { padding:18px 16px; border-bottom:1px solid var(--border); text-align:left; vertical-align:middle; }
.services-table th { font-weight:700; color:var(--muted); font-size:12px; text-transform:uppercase; letter-spacing:.03em; }
.services-table td { vertical-align:middle; }
.services-table input[type='number'] { width:110px; padding:10px 12px; border:1px solid var(--border); border-radius:8px; background:var(--surface); color:var(--text); font-size:14px; }
.services-table input[type='number']:focus { border-color:var(--primary); outline:none; }
.services-table img { width:52px; height:52px; object-fit:cover; border-radius:10px; }
.services-table tr:last-child td { border-bottom:none; }
.total-box { margin-top:28px; padding:24px 28px; border:1px solid var(--border); border-radius:12px; background:rgba(34,197,94,0.06); display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:flex-end; gap:40px; }
.total-box label { font-size:13px; color:var(--muted); display:block; margin-bottom:8px; }
.total-box input[type='number'] { padding:10px 12px; border:1px solid var(--border); border-radius:8px; background:var(--surface); color:var(--text); font-size:14px; min-width:140px; }
.total-box .big { font-size:24px; font-weight:800; color:var(--primary); }
.empty-state { text-align:center; padding:40px; color:var(--muted); font-size:14px; }
</style>
@endpush

@php
    $equipment = $service->serviceEquipment;
    $customerName = trim(($service->customer?->nombre ?? '') . ' ' . ($service->customer?->apellido ?? '')) ?: 'Sin cliente';
    $sparePartsByRefaccion = $service->spareParts->keyBy('refaccion_id');
@endphp

@section('service_content')
<x-ui.card>
    <div class="area-header">
        <div>
            <h1 class="area-title">Cotización</h1>
            <p class="area-subtitle">Folio {{ $service->service_number ?? ('OS-' . $service->id) }}</p>
        </div>
        <a href="{{ route('gestion.servicios.area_endoscopia') }}" class="btn" style="display:inline-flex; align-items:center; gap:8px; padding:10px 18px; border-radius:10px; font-size:14px; font-weight:700; text-decoration:none; background:var(--primary); color:#fff; border:1px solid var(--primary);">
            Volver
        </a>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:20px;">
        <div class="cotizacion-field">
            <label>Cliente</label>
            <div class="value">{{ $customerName }}</div>
        </div>
        <div class="cotizacion-field">
            <label>Equipo</label>
            <div class="value">{{ $equipment?->type_text ?? '—' }}</div>
        </div>
        <div class="cotizacion-field">
            <label>Marca / Modelo / Serie</label>
            <div class="value">
                {{ $equipment?->brand_text ?? '—' }} /
                {{ $equipment?->model_text ?? '—' }} /
                {{ $equipment?->serial_number ?? '—' }}
            </div>
        </div>
        <div class="cotizacion-field">
            <label>Estado</label>
            <div class="value">{{ ucfirst(str_replace('_', ' ', $service->status ?? 'registrado')) }}</div>
        </div>
    </div>

    @if (session('success'))
        <x-ui.card style='margin-bottom:22px; border-color:var(--green);'>
            <strong style='color:var(--green);'>{{ session('success') }}</strong>
        </x-ui.card>
    @endif

    @if ($errors->any())
        <x-ui.card style='margin-bottom:22px; border-color:#ef4444;'>
            <strong style='color:#ef4444;'>Revisa los siguientes campos:</strong>
            <ul style='margin:6px 0 0; padding-left:18px; color:#ef4444;'>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif

    <form method='POST' action='{{ route('gestion.servicios.area_endoscopia.cotizacion.store', $service) }}' id='cotizacion-form'>
        @csrf

        <div class='search-box'>
            <svg width='17' height='17' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2'><circle cx='11' cy='11' r='8'/><path d='M21 21l-4.35-4.35'/></svg>
            <input type='text' id='buscar-refaccion' placeholder='Buscar refacción...'>
        </div>

        @if ($refacciones->isNotEmpty())
            <table class='services-table' id='tabla-refacciones'>
                <thead>
                    <tr>
                        <th style='text-align:center;'>FOTO</th>
                        <th>REFACCIÓN</th>
                        <th style='text-align:center;'>STOCK</th>
                        <th style='text-align:center;'>CANTIDAD</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($refacciones as $refaccion)
                        @php
                            $line = $sparePartsByRefaccion->get($refaccion->id);
                            $cantidad = $line?->cantidad ?? 0;
                        @endphp
                        <tr class='refaccion-row' data-name='{{ $refaccion->name }}' data-subtype='{{ $refaccion->subtype }}'>
                            <td style='text-align:center;'>
                                @if ($refaccion->photo_path)
                                    <img src='{{ asset('storage/' . $refaccion->photo_path) }}' alt='{{ $refaccion->name }}'>
                                @else
                                    <span style='color:var(--muted); font-size:12px;'>Sin foto</span>
                                @endif
                            </td>
                            <td>
                                <div style='font-weight:700;'>{{ $refaccion->name }}</div>
                                <div style='font-size:12px; color:var(--muted);'>{{ $refaccion->subtype }}</div>
                            </td>
                            <td style='text-align:center;'>{{ $refaccion->stock }}</td>
                            <td style='text-align:center;'>
                                <input type='number' name='cantidad[{{ $refaccion->id }}]' value='{{ $cantidad }}' min='0' max='{{ $refaccion->stock }}' class='input-cantidad' data-cantidad-row='{{ $refaccion->id }}'>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class='empty-state'>No hay refacciones registradas.</p>
        @endif

        <div style='margin-top:24px; padding:18px; border:1px dashed var(--border); border-radius:12px;'>
            <label style='font-size:13px; font-weight:700; display:block; margin-bottom:4px;'>Refacción necesaria</label>
            <p style='font-size:12px; color:var(--muted); margin:0 0 12px;'>Si la pieza que buscas no aparece en la lista, agrégala aquí. Estas piezas no tienen stock registrado.</p>
            <div style='display:flex; gap:10px; flex-wrap:wrap;'>
                <input type='text' id='necesaria-nombre' placeholder='Nombre de la refacción' style='flex:1; min-width:220px; padding:10px 12px; border:1px solid var(--border); border-radius:8px; background:var(--surface); color:var(--text); font-size:14px;'>
                <input type='number' id='necesaria-cantidad' value='1' min='1' style='width:110px; padding:10px 12px; border:1px solid var(--border); border-radius:8px; background:var(--surface); color:var(--text); font-size:14px;'>
                <button type='button' id='btn-agregar-necesaria' class='btn' style='display:inline-flex; align-items:center; gap:8px; padding:10px 18px; border-radius:10px; font-size:14px; font-weight:700; background:var(--surface-2); color:var(--text); border:1px solid var(--border); cursor:pointer;'>Agregar</button>
            </div>
            <ul id='lista-necesarias' style='margin:14px 0 0; padding:0; list-style:none; display:flex; flex-direction:column; gap:8px;'>
                @foreach ($service->spareParts->whereNull('refaccion_id') as $part)
                    <li class='necesaria-item' style='display:flex; align-items:center; justify-content:space-between; gap:10px; padding:8px 12px; border:1px solid var(--border); border-radius:8px; background:var(--surface);'>
                        <input type='hidden' name='necesarias[{{ $loop->index }}][nombre]' value='{{ $part->nombre }}'>
                        <input type='hidden' name='necesarias[{{ $loop->index }}][cantidad]' value='{{ $part->cantidad }}'>
                        <span style='font-size:13px;'>{{ $part->nombre }} <span style='color:var(--muted);'>x{{ $part->cantidad }}</span></span>
                        <button type='button' class='necesaria-remove' style='background:none; border:none; color:var(--danger); font-size:18px; cursor:pointer; line-height:1;'>&times;</button>
                    </li>
                @endforeach
            </ul>
        </div>

        <div style='margin-top:24px;'>
            <button type='submit' class='btn' style='display:inline-flex; align-items:center; gap:8px; padding:10px 18px; border-radius:10px; font-size:14px; font-weight:700; background:var(--primary); color:#fff; border:1px solid var(--primary); cursor:pointer;'>Guardar cotización</button>
        </div>
    </form>
</x-ui.card>

@push('scripts')
<script>
    function normalizeText(text) {
        return String(text || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
    }

    function filtrarRefacciones() {
        const term = normalizeText(document.getElementById('buscar-refaccion').value);
        document.querySelectorAll('.refaccion-row').forEach(function(row) {
            const cantidadInput = row.querySelector('.input-cantidad');
            const stock = parseFloat(cantidadInput.getAttribute('max')) || 0;
            let cantidad = parseFloat(cantidadInput.value) || 0;
            if (cantidad > stock) {
                cantidadInput.value = stock;
                cantidad = stock;
            }
            if (cantidad >= 1) {
                row.style.display = '';
                return;
            }
            const name = normalizeText(row.getAttribute('data-name'));
            const subtype = normalizeText(row.getAttribute('data-subtype'));
            const match = name.includes(term) || subtype.includes(term);
            row.style.display = match ? '' : 'none';
        });
    }

    document.getElementById('buscar-refaccion')?.addEventListener('input', filtrarRefacciones);

    document.getElementById('tabla-refacciones')?.addEventListener('input', function(e) {
        if (e.target.classList.contains('input-cantidad')) {
            filtrarRefacciones();
        }
    });

    // ===== Refacciones necesarias (sin stock) =====
    const listaNecesarias = document.getElementById('lista-necesarias');
    const inputNombre = document.getElementById('necesaria-nombre');
    const inputCantidad = document.getElementById('necesaria-cantidad');
    let necesariaIdx = listaNecesarias ? listaNecesarias.querySelectorAll('.necesaria-item').length : 0;

    function agregarNecesaria(nombre, cantidad) {
        const li = document.createElement('li');
        li.className = 'necesaria-item';
        li.style.cssText = 'display:flex; align-items:center; justify-content:space-between; gap:10px; padding:8px 12px; border:1px solid var(--border); border-radius:8px; background:var(--surface);';
        li.innerHTML =
            '<input type="hidden" name="necesarias[' + necesariaIdx + '][nombre]" value="">' +
            '<input type="hidden" name="necesarias[' + necesariaIdx + '][cantidad]" value="' + cantidad + '">' +
            '<span style="font-size:13px;"><span class="necesaria-nombre"></span> <span style="color:var(--muted);">x' + cantidad + '</span></span>' +
            '<button type="button" class="necesaria-remove" style="background:none; border:none; color:var(--danger); font-size:18px; cursor:pointer; line-height:1;">&times;</button>';
        li.querySelector('[name$="[nombre]"]').value = nombre;
        li.querySelector('.necesaria-nombre').textContent = nombre;
        listaNecesarias.appendChild(li);
        necesariaIdx++;
    }

    document.getElementById('btn-agregar-necesaria')?.addEventListener('click', function() {
        const nombre = inputNombre.value.trim();
        const cantidad = Math.max(1, parseInt(inputCantidad.value) || 1);
        if (!nombre) {
            inputNombre.focus();
            return;
        }
        agregarNecesaria(nombre, cantidad);
        inputNombre.value = '';
        inputCantidad.value = 1;
        inputNombre.focus();
    });

    inputNombre?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('btn-agregar-necesaria')?.click();
        }
    });

    listaNecesarias?.addEventListener('click', function(e) {
        const btn = e.target.closest('.necesaria-remove');
        if (btn) btn.closest('.necesaria-item')?.remove();
    });
</script>
@endpush
@endsection