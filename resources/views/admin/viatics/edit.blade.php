@extends('layouts.dashboard')
@section('title', 'Editar Viático')
@section('page-title', 'Editar Viático')
@section('page-sub', 'Modifica los gastos del viaje')

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
        position: absolute; left: 14px;
        width: 20px; height: 20px; color: var(--muted);
        pointer-events: none; flex: 0 0 auto;
    }
    .vt-input-wrap .vt-prefix {
        position: absolute; left: 14px;
        font-size: 16px; font-weight: 700; color: var(--muted);
        pointer-events: none;
    }
    .vt-input {
        width: 100%; padding: 14px 14px 14px 44px;
        border: 2px solid #94a3b8; border-radius: 12px;
        font-size: 16px; font-family: inherit;
        background: var(--surface); color: var(--text);
        outline: none; transition: border .15s, box-shadow .15s;
        -webkit-appearance: none; appearance: none;
    }
    .vt-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(0,122,255,.12);
    }
    .vt-input::placeholder { color: #cbd5e1; }

    textarea.vt-input {
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
        .vt-input { padding: 16px 16px 16px 48px; font-size: 17px; }
        .vt-row-2 { gap: 16px; }
        .vt-chip { padding: 12px 28px; font-size: 15px; }
        .vt-submit { max-width: 400px; margin: 0 auto; padding: 18px; font-size: 17px; }
    }
    @media (min-width: 1024px) {
        .vt-form-grid-desktop {
            display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
        }
    }

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

    /* Delete button */
    .vt-delete-btn {
        display: flex; align-items: center; justify-content: center; gap: 8px;
        width: 100%; padding: 14px; border-radius: 14px;
        background: transparent; color: #ef4444;
        border: 2px solid #ef4444;
        font-size: 15px; font-weight: 700; cursor: pointer;
        font-family: inherit; transition: all .15s;
        margin-top: 12px;
    }
    .vt-delete-btn:hover { background: #fee2e2; }
    .vt-delete-btn svg { width: 18px; height: 18px; }
</style>
@endpush

@section('content')
<div class="vt-page">

    {{-- Header --}}
    <div class="vt-header">
        <a href="{{ route('admin.viatics.index') }}" class="vt-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
        </a>
        <h1 class="vt-header-title">Editar Viático</h1>
    </div>

    {{-- Vehicle selector chips --}}
    <div class="vt-chip-row">
        @foreach($vehicles as $v)
            <button type="button" class="vt-chip {{ $viatic->vehicle_id === $v->id ? 'selected' : '' }}"
                    data-vehicle-id="{{ $v->id }}"
                    onclick="vtSelectVehicle(this, {{ $v->id }})">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17h14M3 17l1.5-5.5A2 2 0 0 1 6.4 10h11.2a2 2 0 0 1 1.9 1.5L21 17M5 17v2M19 17v2"/><circle cx="7.5" cy="17" r="1.5"/><circle cx="16.5" cy="17" r="1.5"/></svg>
                {{ $v->model ?: $v->brand ?: 'Vehículo' }}
            </button>
        @endforeach
        @if($vehicles->isEmpty())
            <button type="button" class="vt-chip selected" data-vehicle-id="">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17h14M3 17l1.5-5.5A2 2 0 0 1 6.4 10h11.2a2 2 0 0 1 1.9 1.5L21 17M5 17v2M19 17v2"/><circle cx="7.5" cy="17" r="1.5"/><circle cx="16.5" cy="17" r="1.5"/></svg>
                Sin vehículo
            </button>
        @endif
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('admin.viatics.update', $viatic) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="vehicle_id" id="vtVehicleId" value="{{ $viatic->vehicle_id ?? '' }}">
        <input type="hidden" name="expense_date" value="{{ $viatic->expense_date?->format('Y-m-d') ?? now()->format('Y-m-d') }}">

        <div class="vt-form-card">
            {{-- Lugar --}}
            <div class="vt-field">
                <label class="vt-label">Lugar</label>
                <div class="vt-input-wrap">
                    <svg class="vt-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <input type="text" name="place" class="vt-input" placeholder="Ej. Guadalajara, Jalisco" value="{{ old('place', $viatic->place) }}">
                </div>
            </div>

            {{-- Casetas & Gasolina --}}
            <div class="vt-row-2">
                <div class="vt-field">
                    <label class="vt-label">Casetas</label>
                    <div class="vt-input-wrap">
                        <span class="vt-prefix">$</span>
                        <input type="number" name="tolls" class="vt-input" placeholder="0.00" step="0.01" min="0" inputmode="decimal" value="{{ old('tolls', $viatic->tolls) }}">
                    </div>
                </div>
                <div class="vt-field">
                    <label class="vt-label">Gasolina</label>
                    <div class="vt-input-wrap">
                        <span class="vt-prefix">$</span>
                        <input type="number" name="fuel" class="vt-input" placeholder="0.00" step="0.01" min="0" inputmode="decimal" value="{{ old('fuel', $viatic->fuel) }}">
                    </div>
                </div>
            </div>

            {{-- Viáticos & Adicional --}}
            <div class="vt-row-2">
                <div class="vt-field">
                    <label class="vt-label">Viáticos</label>
                    <div class="vt-input-wrap">
                        <span class="vt-prefix">$</span>
                        <input type="number" name="meals" class="vt-input" placeholder="0.00" step="0.01" min="0" inputmode="decimal" value="{{ old('meals', $viatic->meals) }}">
                    </div>
                </div>
                <div class="vt-field">
                    <label class="vt-label">Adicional</label>
                    <div class="vt-input-wrap">
                        <span class="vt-prefix">$</span>
                        <input type="number" name="additional" class="vt-input" placeholder="0.00" step="0.01" min="0" inputmode="decimal" value="{{ old('additional', $viatic->additional) }}">
                    </div>
                </div>
            </div>

            {{-- Descripción --}}
            <div class="vt-field">
                <label class="vt-label">Descripción</label>
                <div class="vt-input-wrap">
                    <svg class="vt-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="top:14px; left:14px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8M16 17H8M10 9H8"/></svg>
                    <textarea name="description" class="vt-input" placeholder="Describe el motivo del viaje o gastos adicionales...">{{ old('description', $viatic->description) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <button type="submit" class="vt-submit">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Guardar Cambios
        </button>
    </form>

    {{-- Delete --}}
    <form method="POST" action="{{ route('admin.viatics.destroy', $viatic) }}" onsubmit="return confirm('¿Eliminar este viático? Esta acción no se puede deshacer.')">
        @csrf
        @method('DELETE')
        <button type="submit" class="vt-delete-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            Eliminar Viático
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
