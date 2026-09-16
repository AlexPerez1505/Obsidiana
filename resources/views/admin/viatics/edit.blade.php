@extends('layouts.dashboard')
@section('title', 'Editar Viático')
@section('page-title', 'Editar Viático')
@section('page-sub', 'Modifica los gastos del viaje')

@push('head')
<style>
    .rgrid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px 18px; }
    @media (max-width: 520px) { .rgrid-2 { grid-template-columns: 1fr; } }

    /* Zona para agregar fotos del ticket */
    .vt-upload {
        border: 1.5px dashed var(--border); border-radius: 12px;
        padding: 22px 20px; text-align: center; cursor: pointer;
        transition: border-color .15s, background .15s;
        background: var(--surface-2);
    }
    .vt-upload:hover { border-color: var(--primary); background: var(--primary-soft); }
    .vt-upload-icon {
        width: 42px; height: 42px; border-radius: 10px;
        background: var(--primary-soft); color: var(--primary);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 8px;
    }
    .vt-upload-icon svg { width: 22px; height: 22px; }
    .vt-upload p { margin: 0; font-size: 14px; font-weight: 600; color: var(--text); }
    .vt-upload span { font-size: 12.5px; color: var(--muted); display: block; margin-top: 3px; }

    .vt-photo-previews {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px;
        margin-bottom: 14px;
    }
    .vt-photo-previews:empty { display: none; }
    .vt-photo-thumb {
        position: relative; aspect-ratio: 1; border-radius: 10px; overflow: hidden;
        border: 1px solid var(--border); background: var(--surface-2);
    }
    .vt-photo-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .vt-photo-thumb.is-removed { opacity: .35; }
    .vt-photo-remove {
        position: absolute; top: 5px; right: 5px;
        width: 22px; height: 22px; border-radius: 50%;
        background: rgba(15,23,42,.65); color: #fff; border: none;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
    }
    .vt-photo-remove svg { width: 12px; height: 12px; }
</style>
@endpush

@section('content')

    <x-ui.page-header title="Editar Viático" :back="route('admin.viatics.index')" />

    <form method="POST" action="{{ route('admin.viatics.update', $viatic) }}" enctype="multipart/form-data" id="vtForm">
        @csrf
        @method('PATCH')

        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Datos del viaje</x-ui.section-title>
            <div class="rgrid-2">
                <x-ui.form-group label="Lugar" name="place" placeholder="Ej. Guadalajara, Jalisco" :value="old('place', $viatic->place)" />
                <x-ui.form-group for="vehicle_id" label="Vehículo">
                    <select id="vehicle_id" name="vehicle_id">
                        <option value="">Sin vehículo</option>
                        @foreach ($vehicles as $v)
                            <option value="{{ $v->id }}" @selected((string) old('vehicle_id', $viatic->vehicle_id) === (string) $v->id)>
                                {{ $v->model ?: $v->brand ?: 'Vehículo' }}
                            </option>
                        @endforeach
                    </select>
                </x-ui.form-group>
                <x-ui.form-group label="Fecha" name="expense_date" type="date"
                                 :value="old('expense_date', $viatic->expense_date?->format('Y-m-d'))" />
            </div>
        </x-ui.card>

        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 16px;">Gastos</x-ui.section-title>
            <div class="rgrid-2">
                <x-ui.form-group label="Casetas" name="tolls" type="number" step="0.01" min="0" placeholder="0.00" inputmode="decimal" :value="old('tolls', $viatic->tolls)" />
                <x-ui.form-group label="Gasolina" name="fuel" type="number" step="0.01" min="0" placeholder="0.00" inputmode="decimal" :value="old('fuel', $viatic->fuel)" />
                <x-ui.form-group label="Viáticos" name="meals" type="number" step="0.01" min="0" placeholder="0.00" inputmode="decimal" :value="old('meals', $viatic->meals)" />
                <x-ui.form-group label="Hospedaje" name="lodging" type="number" step="0.01" min="0" placeholder="0.00" inputmode="decimal" :value="old('lodging', $viatic->lodging)" />
                <x-ui.form-group label="Adicional" name="additional" type="number" step="0.01" min="0" placeholder="0.00" inputmode="decimal" :value="old('additional', $viatic->additional)" />
            </div>
            <x-ui.form-group label="Descripción" for="description">
                <textarea id="description" name="description" rows="3" placeholder="Describe el motivo del viaje o gastos adicionales...">{{ old('description', $viatic->description) }}</textarea>
            </x-ui.form-group>
        </x-ui.card>

        <x-ui.card style="margin-bottom:18px;">
            <x-ui.section-title style="margin:0 0 14px;">Fotos del ticket</x-ui.section-title>

            <div class="vt-photo-previews" id="vtPhotoPreviews">
                @foreach($viatic->ticket_photos ?? [] as $ruta)
                    <div class="vt-photo-thumb" data-ruta="{{ $ruta }}">
                        <img src="{{ asset('storage/' . $ruta) }}" alt="Foto del ticket">
                        <button type="button" class="vt-photo-remove" aria-label="Quitar foto" onclick="vtQuitarFotoExistente(this, '{{ $ruta }}')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        </button>
                    </div>
                @endforeach
            </div>
            <div id="vtPhotoPreviewsNuevas" class="vt-photo-previews"></div>
            <div class="vt-upload" onclick="document.getElementById('vtPhotosInput').click()">
                <div class="vt-upload-icon">
                    <x-gravityui-camera />
                </div>
                <p>Agregar fotos del ticket</p>
                <span>Toca para tomar o seleccionar una o varias fotos</span>
            </div>
            <input type="file" id="vtPhotosInput" name="ticket_photos[]" accept="image/*" multiple hidden>
            <div id="vtQuitarFotosBox"></div>
        </x-ui.card>

        <div class="page-foot">
            <a href="{{ route('admin.viatics.index') }}" class="btn btn--ghost">Cancelar</a>
            <x-ui.button>
                <x-gravityui-floppy-disk width="16" height="16" />
                Guardar cambios
            </x-ui.button>
        </div>
    </form>

    {{-- Eliminar --}}
    <form method="POST" action="{{ route('admin.viatics.destroy', $viatic) }}"
          onsubmit="return confirm('¿Eliminar este viático? Esta acción no se puede deshacer.')" style="margin-top:14px;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn--danger">
            <x-gravityui-trash-bin width="16" height="16" />
            Eliminar viático
        </button>
    </form>

<script>
    function vtQuitarFotoExistente(btn, ruta) {
        const thumb = btn.closest('.vt-photo-thumb');
        thumb.classList.add('is-removed');
        btn.disabled = true;

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'quitar_fotos[]';
        input.value = ruta;
        document.getElementById('vtQuitarFotosBox').appendChild(input);
    }

    (function () {
        const input = document.getElementById('vtPhotosInput');
        const previews = document.getElementById('vtPhotoPreviewsNuevas');
        let archivos = [];

        function sincronizarInput() {
            const dt = new DataTransfer();
            archivos.forEach(archivo => dt.items.add(archivo));
            input.files = dt.files;
        }

        function render() {
            previews.innerHTML = '';
            archivos.forEach((archivo, index) => {
                const reader = new FileReader();
                reader.onload = function (event) {
                    const thumb = document.createElement('div');
                    thumb.className = 'vt-photo-thumb';
                    thumb.innerHTML = '<img src="' + event.target.result + '" alt="Foto del ticket">' +
                        '<button type="button" class="vt-photo-remove" aria-label="Quitar foto">' +
                        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg></button>';
                    thumb.querySelector('.vt-photo-remove').addEventListener('click', function () {
                        archivos.splice(index, 1);
                        sincronizarInput();
                        render();
                    });
                    previews.appendChild(thumb);
                };
                reader.readAsDataURL(archivo);
            });
        }

        input.addEventListener('change', function () {
            archivos = archivos.concat(Array.from(input.files));
            sincronizarInput();
            render();
        });
    })();
</script>
@endsection
