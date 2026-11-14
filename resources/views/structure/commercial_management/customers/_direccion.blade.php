{{--
    Dirección del cliente por partes: estado (los 32 de México), municipio
    (se carga del catálogo de INEGI según el estado), colonia, C.P. y calle.
    Con eso el sistema arma el texto completo de `direccion`.

    Espera opcionalmente $customer (al editar). Un cliente viejo que solo
    tiene la dirección escrita de corrido no está obligado a desglosarla:
    se le muestra y se conserva mientras no llene los campos nuevos.
--}}
@php
    $c = $customer ?? null;
    $val = fn (string $campo) => old($campo, $c?->{$campo});
    $soloTextoViejo = $c && ! $c->estado && filled($c->direccion);
    $obligatorio = ! $soloTextoViejo;
@endphp

<div class="dir-bloque" data-dir data-url="{{ route('commercial.clientes.municipios') }}">
    @if ($soloTextoViejo)
        <input type="hidden" name="direccion" value="{{ old('direccion', $c->direccion) }}">
        <p class="dir-vieja">
            <b>Dirección actual:</b> {{ $c->direccion }}<br>
            <small>Se guardó antes de que la dirección fuera por partes. Si la vuelves a capturar abajo, se reemplaza.</small>
        </p>
    @endif

    <div class="rgrid-2">
        <x-ui.form-group for="estado" label="Estado{{ $obligatorio ? ' *' : '' }}">
            <select id="estado" name="estado" data-dir-estado @required($obligatorio)>
                <option value="">Selecciona un estado</option>
                @foreach (\App\Support\Mexico::ESTADOS as $estado)
                    <option value="{{ $estado }}" @selected($val('estado') === $estado)>{{ $estado }}</option>
                @endforeach
            </select>
        </x-ui.form-group>

        <x-ui.form-group for="municipio" label="Municipio / Alcaldía{{ $obligatorio ? ' *' : '' }}">
            <select id="municipio" name="municipio" data-dir-municipio data-valor="{{ $val('municipio') }}" @required($obligatorio) disabled>
                <option value="">Primero elige el estado</option>
            </select>
            {{-- Solo si INEGI no responde: se escribe a mano. --}}
            <input type="text" id="municipio_texto" name="municipio" data-dir-municipio-texto maxlength="120"
                   value="{{ $val('municipio') }}" placeholder="Escribe el municipio" hidden disabled>
        </x-ui.form-group>

        <x-ui.form-group label="Colonia" name="colonia" placeholder="Ej. Centro" maxlength="150" :value="$c?->colonia" />
        <x-ui.form-group label="Código postal" name="codigo_postal" placeholder="Ej. 50000" inputmode="numeric" maxlength="5" pattern="[0-9]{5}" :value="$c?->codigo_postal" />

        <div style="grid-column:1 / -1;">
            <x-ui.form-group label="Calle y número" name="calle" placeholder="Ej. Av. Juárez 120, int. 3 (opcional)" maxlength="200" :value="$c?->calle" />
        </div>
    </div>

    <p class="dir-previa" data-dir-previa hidden></p>
</div>

@once
    <style>
        .dir-vieja { margin:0 0 14px; padding:10px 12px; border:1px dashed var(--border); border-radius:10px; font-size:13.5px; }
        .dir-vieja small { color:var(--muted); }
        .dir-previa { margin:6px 0 0; font-size:13px; color:var(--muted); }
        .dir-previa b { color:var(--text); font-weight:600; }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-dir]').forEach(function (caja) {
                var estado = caja.querySelector('[data-dir-estado]');
                var sel = caja.querySelector('[data-dir-municipio]');
                var txt = caja.querySelector('[data-dir-municipio-texto]');
                var previa = caja.querySelector('[data-dir-previa]');
                var requerido = sel.required;
                var cache = {};

                function aTexto(valor) {
                    sel.hidden = true; sel.disabled = true;
                    txt.hidden = false; txt.disabled = false; txt.required = requerido;
                    if (valor !== undefined) txt.value = valor;
                }

                function llenar(lista, valor) {
                    sel.hidden = false; txt.hidden = true; txt.disabled = true; txt.required = false;
                    sel.innerHTML = '';
                    var vacia = document.createElement('option');
                    vacia.value = '';
                    vacia.textContent = 'Selecciona el municipio';
                    sel.appendChild(vacia);
                    lista.forEach(function (m) {
                        var o = document.createElement('option');
                        o.value = m; o.textContent = m;
                        if (m === valor) o.selected = true;
                        sel.appendChild(o);
                    });
                    // Un valor guardado que ya no viene en la lista no se pierde.
                    if (valor && lista.indexOf(valor) === -1) {
                        var extra = document.createElement('option');
                        extra.value = valor; extra.textContent = valor; extra.selected = true;
                        sel.appendChild(extra);
                    }
                    sel.disabled = false;
                    sel.required = requerido;
                }

                function cargar(valor) {
                    var e = estado.value;
                    if (!e) {
                        llenar([], '');
                        sel.options[0].textContent = 'Primero elige el estado';
                        sel.disabled = true;
                        return;
                    }
                    if (cache[e]) { llenar(cache[e], valor); return; }
                    sel.disabled = true;
                    sel.innerHTML = '<option value="">Cargando municipios…</option>';
                    fetch(caja.dataset.url + '?estado=' + encodeURIComponent(e), { headers: { 'Accept': 'application/json' } })
                        .then(function (r) { return r.ok ? r.json() : { municipios: [] }; })
                        .then(function (d) {
                            var lista = d.municipios || [];
                            if (!lista.length) { aTexto(valor || ''); return; }
                            cache[e] = lista;
                            llenar(lista, valor);
                        })
                        .catch(function () { aTexto(valor || ''); });
                }

                function pintarPrevia() {
                    var v = function (n) { var el = caja.querySelector('[name="' + n + '"]:not([disabled])'); return el ? el.value.trim() : ''; };
                    var partes = [v('calle'), v('colonia') && 'Col. ' + v('colonia'), v('codigo_postal') && 'C.P. ' + v('codigo_postal'), v('municipio'), v('estado')].filter(Boolean);
                    previa.hidden = !partes.length;
                    previa.innerHTML = '';
                    if (partes.length) {
                        var b = document.createElement('b'); b.textContent = 'Así queda: ';
                        previa.appendChild(b);
                        previa.appendChild(document.createTextNode(partes.join(', ')));
                    }
                }

                estado.addEventListener('change', function () { cargar(''); pintarPrevia(); });
                caja.addEventListener('input', pintarPrevia);
                caja.addEventListener('change', pintarPrevia);

                var cp = caja.querySelector('[name="codigo_postal"]');
                if (cp) cp.addEventListener('input', function () { cp.value = cp.value.replace(/\D/g, '').slice(0, 5); });

                cargar(sel.dataset.valor || '');
                pintarPrevia();
            });
        });
    </script>
@endonce
