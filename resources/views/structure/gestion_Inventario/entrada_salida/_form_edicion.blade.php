{{--
    Corregir una entrada dentro de su propio detalle (Editar abre esta misma
    pantalla con el formulario arriba). Espera $movimiento, $producto,
    $estadosGenerales y $puedeEditarPiezas.
--}}
@php
    $usado = $movimiento->condicion === 'usado';
    $checklist = $movimiento->checklist_recepcion ?? [];
    $sinSerie = $movimiento->seriales->filter(fn ($pieza) => ! $pieza->vendido
        && trim((string) $pieza->no_serie) === '')->count();
    // Los puntos se agrupan como se preguntaron al recibir.
    $grupos = collect($checklist)->groupBy(fn ($p, $llave) => \App\Support\ChecklistRecepcion::grupoDe($llave, $p), preserveKeys: true);
@endphp

    <form method="POST" action="{{ route('inventory.movimientos.update', $movimiento) }}" id="editar" class="mv-editar" data-live="off" style="display:block; margin:18px 0; scroll-margin-top:90px;">
        @csrf
        @method('PUT')

        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 6px;">Editar datos de la entrada</x-ui.section-title>
            <p class="campo-nota" style="margin:0 0 16px;">
                Equipo {{ $usado ? 'usado' : 'nuevo' }} · {{ $movimiento->quantity }} {{ $movimiento->unit }}.
                El equipo y la cantidad no se cambian aquí: si se capturó mal, elimina la entrada y regístrala de nuevo.
            </p>

            <div class="rgrid-campos">
                <x-ui.form-group label="Fecha de llegada *" for="movement_date">
                    <input id="movement_date" type="date" name="movement_date" required
                           value="{{ old('movement_date', $movimiento->movement_date?->format('Y-m-d')) }}">
                    @error('movement_date')<p class="err">{{ $message }}</p>@enderror
                </x-ui.form-group>

                <x-ui.form-group label="Fecha de fabricación" for="fecha_fabricacion">
                    <span class="fecha-limpiable">
                        <input id="fecha_fabricacion" type="date" name="fecha_fabricacion"
                               value="{{ old('fecha_fabricacion', $movimiento->fecha_fabricacion?->format('Y-m-d')) }}">
                        <button type="button" class="fecha-limpiar" data-limpiar-fecha="fecha_fabricacion"
                                title="Quitar la fecha" aria-label="Quitar la fecha de fabricación">×</button>
                    </span>
                    @error('fecha_fabricacion')<p class="err">{{ $message }}</p>@enderror
                </x-ui.form-group>

                @unless ($usado)
                    <x-ui.form-group label="Fecha de caducidad" for="fecha_caducidad">
                        <span class="fecha-limpiable">
                            <input id="fecha_caducidad" type="date" name="fecha_caducidad"
                                   value="{{ old('fecha_caducidad', $movimiento->fecha_caducidad?->format('Y-m-d')) }}">
                            <button type="button" class="fecha-limpiar" data-limpiar-fecha="fecha_caducidad"
                                    title="Quitar la fecha" aria-label="Quitar la fecha de caducidad">×</button>
                        </span>
                        @error('fecha_caducidad')<p class="err">{{ $message }}</p>@enderror
                    </x-ui.form-group>
                @endunless

                <x-ui.form-group label="Referencia" for="reference">
                    <input id="reference" type="text" name="reference" maxlength="255"
                           value="{{ old('reference', $movimiento->reference) }}" placeholder="Opcional">
                </x-ui.form-group>
            </div>

            <x-ui.form-group label="Notas de la entrada" for="notas">
                <textarea id="notas" name="notas" rows="3" maxlength="1000">{{ old('notas', $movimiento->notes) }}</textarea>
            </x-ui.form-group>
        </x-ui.card>

        @if ($usado)
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title style="margin:0 0 14px;">¿En qué estado general llegó?</x-ui.section-title>
                <div class="opciones">
                    @foreach ($estadosGenerales as $valor => $texto)
                        @php([$titulo, $detalle] = array_pad(explode(' · ', $texto, 2), 2, ''))
                        <label class="opcion">
                            <input type="radio" name="estado_general" value="{{ $valor }}"
                                   @checked(old('estado_general', $movimiento->estado_general) === $valor)>
                            <span>
                                <span class="t">{{ $titulo }}</span>
                                <span class="d">{{ $detalle }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('estado_general')<p class="err">{{ $message }}</p>@enderror
            </x-ui.card>

            @if ($checklist)
                <x-ui.card style="margin-bottom:18px;">
                    <x-ui.section-title style="margin:0 0 6px;">Checklist de recepción</x-ui.section-title>
                    <p class="campo-nota" style="margin:0 0 6px;">
                        Corrige las respuestas o las notas. Cambiar una respuesta no cambia los procesos de las piezas:
                        eso se ajusta en el detalle de la entrada, con <b>Editar procesos</b>.
                    </p>

                    @foreach ($grupos as $grupo => $puntos)
                        <div class="chk-grupo">
                            <h4>{{ $grupo }}</h4>
                            @foreach ($puntos as $llave => $punto)
                                @php($r = old("checklist.$llave.r", $punto['r'] ?? null))
                                <div class="chk-punto {{ $r === 'no' ? 'con-nota' : '' }}" data-chk-punto>
                                    <span class="txt">{{ \App\Support\ChecklistRecepcion::titulo($llave, $punto) }}</span>
                                    <span class="tri">
                                        @foreach (['si' => 'Sí', 'no' => 'No', 'na' => 'N/A'] as $valor => $etiqueta)
                                            <label>
                                                <input type="radio" name="checklist[{{ $llave }}][r]" value="{{ $valor }}" @checked($r === $valor)>
                                                <span>{{ $etiqueta }}</span>
                                            </label>
                                        @endforeach
                                    </span>
                                    <input type="text" class="nota" name="checklist[{{ $llave }}][nota]" maxlength="300"
                                           placeholder="¿Qué tiene?" value="{{ old("checklist.$llave.nota", $punto['nota'] ?? '') }}">
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </x-ui.card>
            @endif
        @endif

        @if ($puedeEditarPiezas && $sinSerie > 0)
            <x-ui.card style="margin-bottom:18px;">
                <x-ui.section-title>Generar números de serie</x-ui.section-title>
                <p class="campo-nota">Hay {{ $sinSerie }} pieza(s) sin serie. Se generarán series propias solo para las que faltan;
                    no se modificarán las existentes ni las piezas vendidas. Este botón también guarda los cambios del formulario.</p>
                <button type="submit" name="generar_series" value="1" class="btn btn--ghost">Generar series faltantes y guardar</button>
            </x-ui.card>
        @endif

        <x-ui.card>
            <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <button type="submit" class="btn">Guardar cambios</button>
                <a href="{{ route('inventory.movimientos.show', $movimiento) }}" class="btn btn--ghost">Cancelar</a>

                {{-- Las series y fotos de cada pieza viven en el producto. --}}
                @if ($puedeEditarPiezas)
                    <a href="{{ route('inventory.productos.edit', $producto) }}" class="link" style="margin-left:auto;">
                        Editar las piezas (series y fotos) →
                    </a>
                @endif
            </div>
        </x-ui.card>
    </form>

    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Las fechas opcionales se pueden dejar vacías con su ×.
            document.querySelectorAll('[data-limpiar-fecha]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const campo = document.getElementById(btn.dataset.limpiarFecha);
                    if (campo) { campo.value = ''; campo.focus(); }
                });
            });

            // La nota se abre donde el punto quedó en "No".
            document.querySelectorAll('[data-chk-punto]').forEach(function (punto) {
                punto.addEventListener('change', function () {
                    punto.classList.toggle('con-nota', punto.querySelector('input[type=radio]:checked')?.value === 'no');
                });
            });
        });
        </script>
    @endpush
