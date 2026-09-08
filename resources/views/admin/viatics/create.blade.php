@extends('layouts.dashboard')
@section('title', 'Nuevo Viático')
@section('page-title', 'Nuevo Viático')
@section('page-sub', 'Registra los gastos de viaje')

@push('head')
<style>
    .vt-page {
        max-width: 100%; margin: 0; padding: 0 4px;
    }

    /* Header */
    .vt-header {
        display: flex; align-items: center; gap: 12px;
        margin-bottom: 20px;
    }
    .vt-back {
        display: inline-flex; align-items: center; justify-content: center;
        width: 42px; height: 42px; border-radius: 12px;
        border: 1.5px solid #94a3b8; background: var(--surface);
        color: var(--text); text-decoration: none; flex: 0 0 auto;
        transition: all .15s;
    }
    .vt-back:hover { background: var(--surface-2); border-color: var(--primary); color: var(--primary); }
    .vt-back svg { width: 20px; height: 20px; }
    .vt-header-title {
        font-size: 22px; font-weight: 800; color: var(--primary);
        margin: 0; line-height: 1.2;
    }

    /* Vehicle selector chips */
    .vt-chip-row {
        display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;
    }
    .vt-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 10px 22px; border-radius: 999px;
        border: 2px solid #94a3b8; background: var(--surface);
        font-size: 14px; font-weight: 700; color: var(--text);
        cursor: pointer; transition: all .15s; font-family: inherit;
    }
    .vt-chip svg { width: 18px; height: 18px; }
    .vt-chip:hover { border-color: var(--primary); }
    .vt-chip.selected {
        background: var(--primary); color: #fff;
        border-color: var(--primary);
        box-shadow: 0 4px 14px rgba(0,122,255,.25);
    }
    .vt-chip.selected svg { color: #fff; }

    /* Form card */
    .vt-form-card {
        background: var(--surface); border: 1.5px solid #94a3b8;
        border-radius: 18px; padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,.04);
        margin-bottom: 20px;
    }

    .vt-field { margin-bottom: 16px; }
    .vt-field:last-child { margin-bottom: 0; }
    .vt-label {
        display: block; font-size: 12px; font-weight: 700;
        text-transform: uppercase; letter-spacing: .04em;
        color: var(--muted); margin: 0 0 7px;
    }
    .vt-input-wrap {
        position: relative; display: flex; align-items: center;
    }
    .vt-input-wrap .vt-input-icon {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
        width: 20px; height: 20px; color: var(--muted);
        pointer-events: none; flex: 0 0 auto; display: block;
    }
    /* La descripción es un textarea alto: el ícono se queda fijo arriba,
       no centrado verticalmente como en los inputs de una sola línea. */
    .vt-input-wrap .vt-input-icon--top {
        top: 14px; transform: none;
    }
    .vt-input-wrap .vt-prefix {
        position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
        font-size: 16px; font-weight: 700; color: var(--muted);
        pointer-events: none;
    }
    /*
       El layout general (layouts/dashboard) ya trae un estilo genérico
       para "input[type=text], input[type=number], textarea..." que, por
       especificidad CSS, le gana a una sola clase como ".vt-input" (el
       selector con atributo+elemento pesa más que una sola clase). Por
       eso se escribe siempre calificado con ".vt-form-card" por delante:
       dos clases juntas sí superan esa especificidad y el padding-left
       que deja espacio para el ícono se respeta de verdad.
    */
    .vt-form-card .vt-input {
        width: 100%; padding: 14px 14px 14px 44px;
        border: 2px solid #94a3b8; border-radius: 12px;
        font-size: 16px; font-family: inherit;
        background: var(--surface); color: var(--text);
        outline: none; transition: border .15s, box-shadow .15s;
        -webkit-appearance: none; appearance: none;
    }
    .vt-form-card .vt-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(0,122,255,.12);
    }
    .vt-form-card .vt-input::placeholder { color: #cbd5e1; }

    .vt-form-card textarea.vt-input {
        resize: vertical; min-height: 90px; padding-top: 14px;
        line-height: 1.5;
    }

    .vt-row-2 {
        display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
    }

    /* Desktop responsive */
    @media (min-width: 768px) {
        .vt-page { max-width: 100%; }
        .vt-header-title { font-size: 26px; }
        .vt-form-card { padding: 28px 32px; }
        .vt-field { margin-bottom: 20px; }
        .vt-label { font-size: 13px; }
        .vt-form-card .vt-input { padding: 16px 16px 16px 48px; font-size: 17px; }
        .vt-row-2 { gap: 16px; }
        .vt-chip { padding: 12px 28px; font-size: 15px; }
        .vt-submit { max-width: 400px; margin: 0 auto; padding: 18px; font-size: 17px; }
        .vt-upload { max-width: 400px; margin: 0 auto 20px; }
    }
    @media (min-width: 1024px) {
        .vt-form-grid-desktop {
            display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
        }
    }

    /* Upload zone */
    .vt-upload {
        border: 2.5px dashed #94a3b8; border-radius: 14px;
        padding: 28px 20px; text-align: center; cursor: pointer;
        transition: border-color .15s, background .15s;
        background: var(--surface-2);
    }
    .vt-upload:hover { border-color: var(--primary); background: var(--primary-soft); }
    .vt-upload-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: var(--primary-soft); color: var(--primary);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 10px;
        border: 1.5px solid #94a3b8;
    }
    .vt-upload-icon svg { width: 26px; height: 26px; }
    .vt-upload p { margin: 0; font-size: 14px; font-weight: 700; color: var(--text); }
    .vt-upload span { font-size: 12px; color: var(--muted); display: block; margin-top: 3px; }

    /* Submit button */
    .vt-submit {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        width: 100%; padding: 16px; border: none; border-radius: 14px;
        background: var(--primary); color: #fff;
        font-size: 16px; font-weight: 800; cursor: pointer;
        font-family: inherit; transition: background .15s;
        box-shadow: 0 4px 16px rgba(0,122,255,.25);
    }
    .vt-submit:hover { background: var(--primary-strong); }
    .vt-submit svg { width: 20px; height: 20px; }

    @media (min-width: 768px) {
        .vt-page { max-width: 100%; }
    }
</style>
@endpush

@section('content')
<div class="vt-page">

    {{-- Header --}}
    <div class="vt-header">
        <a href="{{ route('admin.viatics.index') }}" class="vt-back">
            <x-gravityui-arrow-chevron-left />
        </a>
        <h1 class="vt-header-title">Nuevo Viático</h1>
    </div>

    {{-- Vehicle selector chips --}}
    <div class="vt-chip-row">
        @foreach($vehicles as $v)
            <button type="button" class="vt-chip {{ $loop->first ? 'selected' : '' }}"
                    data-vehicle-id="{{ $v->id }}"
                    onclick="vtSelectVehicle(this, {{ $v->id }})">
                <x-gravityui-car />
                {{ $v->model ?: $v->brand ?: 'Vehículo' }}
            </button>
        @endforeach
        @if($vehicles->isEmpty())
            <button type="button" class="vt-chip selected" data-vehicle-id="">
                <x-gravityui-car />
                Sin vehículo
            </button>
        @endif
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('admin.viatics.store') }}">
        @csrf
        <input type="hidden" name="vehicle_id" id="vtVehicleId" value="{{ $vehicles->first()?->id ?? '' }}">
        <input type="hidden" name="expense_date" value="{{ now()->format('Y-m-d') }}">

        <div class="vt-form-card">
            {{-- Lugar --}}
            <div class="vt-field">
                <label class="vt-label">Lugar</label>
                <div class="vt-input-wrap">
                    <x-gravityui-map-pin class="vt-input-icon" />
                    <input type="text" name="place" class="vt-input" placeholder="Ej. Guadalajara, Jalisco">
                </div>
            </div>

            {{-- Casetas & Gasolina --}}
            <div class="vt-row-2">
                <div class="vt-field">
                    <label class="vt-label">Casetas</label>
                    <div class="vt-input-wrap">
                        <span class="vt-prefix">$</span>
                        <input type="number" name="tolls" class="vt-input" placeholder="0.00" step="0.01" min="0" inputmode="decimal">
                    </div>
                </div>
                <div class="vt-field">
                    <label class="vt-label">Gasolina</label>
                    <div class="vt-input-wrap">
                        <span class="vt-prefix">$</span>
                        <input type="number" name="fuel" class="vt-input" placeholder="0.00" step="0.01" min="0" inputmode="decimal">
                    </div>
                </div>
            </div>

            {{-- Viáticos & Adicional --}}
            <div class="vt-row-2">
                <div class="vt-field">
                    <label class="vt-label">Viáticos</label>
                    <div class="vt-input-wrap">
                        <span class="vt-prefix">$</span>
                        <input type="number" name="meals" class="vt-input" placeholder="0.00" step="0.01" min="0" inputmode="decimal">
                    </div>
                </div>
                <div class="vt-field">
                    <label class="vt-label">Adicional</label>
                    <div class="vt-input-wrap">
                        <span class="vt-prefix">$</span>
                        <input type="number" name="additional" class="vt-input" placeholder="0.00" step="0.01" min="0" inputmode="decimal">
                    </div>
                </div>
            </div>

            {{-- Descripción --}}
            <div class="vt-field">
                <label class="vt-label">Descripción</label>
                <div class="vt-input-wrap">
                    <x-gravityui-file-text class="vt-input-icon vt-input-icon--top" />
                    <textarea name="description" class="vt-input" placeholder="Describe el motivo del viaje o gastos adicionales..."></textarea>
                </div>
            </div>
        </div>

        {{-- Upload zone --}}
        <div class="vt-upload" onclick="alert('Subida de foto próximamente')" style="margin-bottom:20px;">
            <div class="vt-upload-icon">
                <x-gravityui-camera />
            </div>
            <p>Agregar foto del ticket</p>
            <span>Toca para tomar o seleccionar una foto</span>
        </div>

        {{-- Submit --}}
        <button type="submit" class="vt-submit">
            <x-gravityui-floppy-disk />
            Guardar Viático
        </button>
    </form>

</div>

<script>
    function vtSelectVehicle(btn, id) {
        document.querySelectorAll('.vt-chip').forEach(c => c.classList.remove('selected'));
        btn.classList.add('selected');
        document.getElementById('vtVehicleId').value = id;
    }
</script>
@endsection
