@extends('layouts.qr')

@section('title', 'Verificación QR - ' . ($service->service_number ?? 'Servicio'))

@section('content')
    @php
        $customerName = trim(($service->customer->nombre ?? '') . ' ' . ($service->customer->apellido ?? ''));
        $techName = trim(($service->externalTechnician->nombre ?? '') . ' ' . ($service->externalTechnician->apellidos ?? ''));
        $equipment = $service->serviceEquipment;
        $requiresCode = $tracking->serviceStep->slug === 'notificacion-llegada-tecnico' && $tracking->verification_code && $tracking->status === 'pendiente';
    @endphp

    <h1>{{ $service->service_number ?? 'Servicio' }}</h1>
    <p class="sub">{{ $tracking->serviceStep->name ?? 'Paso del servicio' }}</p>

    @if (session('success'))
        <div class="alert alert--ok">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert--err">{{ session('error') }}</div>
    @endif

    <table>
        <tr>
            <th>Cliente</th>
            <td>{{ $customerName ?: 'N/A' }}</td>
        </tr>
        <tr>
            <th>Técnico</th>
            <td>{{ $techName ?: 'N/A' }}</td>
        </tr>
        <tr>
            <th>Equipo</th>
            <td>{{ $equipment->type_text ?? 'N/A' }} {{ $equipment->brand_text ?? '' }} {{ $equipment->model_text ?? '' }}</td>
        </tr>
        <tr>
            <th>Estado del paso</th>
            <td>
                <span class="badge {{ $tracking->status === 'pendiente' ? 'badge--warn' : 'badge--ok' }}">
                    {{ ucfirst($tracking->status) }}
                </span>
            </td>
        </tr>
    </table>

    @if ($tracking->status === 'pendiente')
        @if ($requiresCode)
            <div class="section">
                <form action="{{ route('qr.verify-code', ['token' => $tracking->qr_token ?? $service->qr_token]) }}" method="POST">
                    @csrf
                    <label for="verification_code">Código de verificación</label>
                    <input type="text" id="verification_code" name="verification_code" class="code-input"
                           maxlength="4" inputmode="numeric" pattern="[0-9]{4}" required
                           placeholder="----" autocomplete="off">
                    <button type="submit" class="btn">Validar código</button>
                </form>
            </div>
        @else
            <div class="section">
                <p class="muted">Este paso no requiere código de verificación.</p>
                <form action="{{ route('qr.complete', ['token' => $tracking->qr_token ?? $service->qr_token]) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn">Marcar paso como completado</button>
                </form>
            </div>
        @endif
    @else
        <div class="section">
            <p class="muted">Este paso ya fue completado.</p>
        </div>
    @endif
@endsection
