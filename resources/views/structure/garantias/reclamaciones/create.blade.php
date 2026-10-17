@extends('layouts.dashboard')

@section('title', 'Nueva reclamación')
@section('page-title', 'Registrar reclamación')
@section('page-sub', 'Toda falla reportada recibe folio, aunque luego no proceda la garantía')

@section('content')
    @include('structure.garantias._estilos')

    <x-ui.page-header title="Nueva reclamación" :back="route('garantias.reclamaciones.index')" />

    <form method="POST" action="{{ route('garantias.reclamaciones.store') }}" enctype="multipart/form-data" class="gt-2">
        @csrf
        <input type="hidden" name="garantia_id" id="garantia_id" value="{{ old('garantia_id', $garantia?->id) }}">

        <div class="gt-col">
            <div class="card" style="padding:0;">
                <div class="card-head"><h3>1. Equipo</h3></div>
                <div class="gt-form">
                    <div id="gtElegida" class="gt-aviso azul" style="margin-top:14px; {{ $garantia ? '' : 'display:none;' }}">
                        <div style="flex:1;">
                            <b id="gtElegidaT">{{ $garantia ? $garantia->folio.' · '.$garantia->equipoNombre() : '' }}</b>
                            <span id="gtElegidaS">{{ $garantia ? $garantia->clienteNombre().' · serie '.($garantia->no_serie ?: '—').' · '.$garantia->semaforoTexto().' (vence '.$garantia->vence->format('d/m/Y').')' : '' }}</span>
                        </div>
                        <button type="button" class="btn-icono" id="gtQuitar" aria-label="Quitar"><x-ui.icon name="x" :size="16" /></button>
                    </div>

                    <div id="gtBuscarBloque" style="{{ $garantia ? 'display:none;' : '' }}">
                        <x-ui.form-group label="Número de serie o folio de garantía" for="no_serie">
                            <input type="text" id="no_serie" name="no_serie" value="{{ old('no_serie') }}" autocomplete="off" placeholder="Escribe para buscar…">
                        </x-ui.form-group>
                        <div class="card" id="gtResultados" style="padding:0; display:none; margin-top:8px;"></div>
                        <p class="campo-nota" style="margin:8px 0 0;">Si no aparece, escribe la serie y qué equipo es: la reclamación se registra igual y se liga después.</p>
                        <div class="gt-form-grid">
                            <x-ui.form-group label="Equipo (si no se encontró)" name="equipo" placeholder="Gastroscopio Olympus GIF-H190" />
                            <x-ui.form-group label="Cliente" for="customer_id">
                                <select id="customer_id" name="customer_id">
                                    <option value="">— Elige —</option>
                                    @foreach ($clientes as $c)<option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>{{ trim($c->nombre.' '.$c->apellido) }}</option>@endforeach
                                </select>
                            </x-ui.form-group>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card" style="padding:0;">
                <div class="card-head"><h3>2. Falla reportada</h3></div>
                <div class="gt-form">
                    <x-ui.form-group label="Síntomas y descripción de la falla *" for="sintomas">
                        <textarea id="sintomas" name="sintomas" rows="5" required minlength="5" placeholder="Qué pasa, desde cuándo, en qué procedimiento, mensajes de error…">{{ old('sintomas') }}</textarea>
                    </x-ui.form-group>
                    <label class="gt-check" style="margin-top:14px;">
                        <input type="checkbox" name="es_seguridad" value="1" @checked(old('es_seguridad'))>
                        <span><b>Posible incidente de seguridad</b><br><span class="gt-s">Afectó o pudo afectar a un paciente o usuario. Se escala a tecnovigilancia aunque la garantía esté vencida.</span></span>
                    </label>
                    <x-ui.form-group label="Evidencia (fotos, video, documentos)" for="archivos">
                        <input type="file" id="archivos" name="archivos[]" multiple accept="image/*,video/*,application/pdf">
                    </x-ui.form-group>
                </div>
            </div>
        </div>

        <div class="gt-col">
            <div class="card" style="padding:0;">
                <div class="card-head"><h3>3. Contacto y atención</h3></div>
                <div class="gt-form">
                    <x-ui.form-group label="Canal *" for="canal">
                        <select id="canal" name="canal">
                            @foreach (\App\Models\GarantiaReclamacion::CANALES as $k => $t)
                                @continue($k === 'portal')
                                <option value="{{ $k }}" @selected(old('canal') === $k)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </x-ui.form-group>
                    <x-ui.form-group label="Quién reporta" name="contacto_nombre" placeholder="Nombre y puesto" />
                    <x-ui.form-group label="Teléfono" name="contacto_telefono" inputmode="tel" />
                    <x-ui.form-group label="Correo" name="contacto_correo" type="email" />
                    <x-ui.form-group label="Prioridad *" for="prioridad">
                        <select id="prioridad" name="prioridad">
                            @foreach (\App\Models\GarantiaReclamacion::PRIORIDADES as $k => [$t])
                                <option value="{{ $k }}" @selected(old('prioridad', 'media') === $k)>{{ $t }} · {{ config('garantias.horas_resolucion')[$k] ?? '' }} h para resolver</option>
                            @endforeach
                        </select>
                    </x-ui.form-group>
                    <x-ui.form-group label="Responsable" for="responsable_id">
                        <select id="responsable_id" name="responsable_id">
                            <option value="">Yo ({{ auth()->user()->name }})</option>
                            @foreach ($responsables as $u)<option value="{{ $u->id }}" @selected(old('responsable_id') == $u->id)>{{ $u->name }}</option>@endforeach
                        </select>
                    </x-ui.form-group>
                </div>
                <div class="gt-acciones"><button type="submit" class="btn">Registrar y generar folio</button></div>
            </div>
        </div>
    </form>

    @push('scripts')
    <script>
    (function () {
        var campo = document.getElementById('no_serie');
        var caja = document.getElementById('gtResultados');
        var oculto = document.getElementById('garantia_id');
        var elegida = document.getElementById('gtElegida');
        var bloque = document.getElementById('gtBuscarBloque');
        var t;

        function elegir(g) {
            oculto.value = g.id;
            document.getElementById('gtElegidaT').textContent = g.folio + ' · ' + g.equipo;
            document.getElementById('gtElegidaS').textContent = g.cliente + ' · serie ' + (g.serie || '—') + ' · ' + g.semaforo + ' (vence ' + g.vence + ')';
            elegida.style.display = '';
            bloque.style.display = 'none';
            caja.style.display = 'none';
        }

        document.getElementById('gtQuitar').addEventListener('click', function () {
            oculto.value = '';
            elegida.style.display = 'none';
            bloque.style.display = '';
            campo.focus();
        });

        campo.addEventListener('input', function () {
            clearTimeout(t);
            var q = campo.value.trim();
            if (q.length < 2) { caja.style.display = 'none'; return; }
            t = setTimeout(function () {
                fetch('{{ route('garantias.reclamaciones.buscar-serie') }}?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (lista) {
                        caja.innerHTML = '';
                        if (!lista.length) { caja.style.display = 'none'; return; }
                        lista.forEach(function (g) {
                            var a = document.createElement('a');
                            a.href = '#';
                            a.className = 'gt-fila';
                            a.innerHTML = '<div><div class="t"></div><div class="s"></div></div><div class="der"><span class="badge"></span></div>';
                            a.querySelector('.t').textContent = g.folio + ' · ' + g.equipo;
                            a.querySelector('.s').textContent = g.cliente + ' · serie ' + (g.serie || '—');
                            var b = a.querySelector('.badge');
                            b.textContent = g.semaforo;
                            if (g.badge) b.classList.add(g.badge);
                            a.addEventListener('click', function (e) { e.preventDefault(); elegir(g); });
                            caja.appendChild(a);
                        });
                        caja.style.display = '';
                    });
            }, 250);
        });
    })();
    </script>
    @endpush
@endsection
