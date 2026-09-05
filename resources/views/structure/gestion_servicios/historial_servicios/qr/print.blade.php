@extends('layouts.app')

@section('title', 'Imprimir QR · ' . $service->service_number)
@section('card-class', 'card--wide')

@section('content')
    <div id="print-area" style="text-align:center;">
        <h1 style="margin:0 0 4px; font-size:20px;">Orden de servicio {{ $service->service_number }}</h1>
        <p style="color:var(--muted); margin:0 0 18px; font-size:13px;">
            {{ $service->service_type === 'externo' ? 'Mantenimiento externo' : 'Mantenimiento interno' }}
        </p>

        <div style="display:inline-block; border:1px solid var(--border); border-radius:12px; padding:14px; background:#fff; margin-bottom:16px;">
            {!! \App\Support\CodigoQr::svg(route('qr.show', $service->qr_token), 260) !!}
        </div>

        <div style="font-family:monospace; word-break:break-all; background:var(--surface-2); padding:10px; border-radius:8px; font-size:13px; margin-bottom:10px;">
            {{ $service->qr_token }}
        </div>

        <p style="font-size:13px; color:var(--muted); margin:0 0 16px;">
            Escanea el código para actualizar el estado de la orden.
            <br>
            <strong>Válido hasta:</strong> {{ $service->qr_expires_at?->format('d/m/Y H:i') ?? '—' }}
        </p>

        <div style="text-align:left; border-top:1px solid var(--border); padding-top:16px; font-size:13px; color:var(--text);">
            <p style="margin:0 0 6px;"><strong>Cliente:</strong> {{ $service->customer?->nombre ?? '—' }} {{ $service->customer?->apellido ?? '' }}</p>
            <p style="margin:0 0 6px;"><strong>Equipo:</strong> {{ $service->serviceEquipment?->type_text ?? '—' }} {{ $service->serviceEquipment?->brand_text ?? '' }} {{ $service->serviceEquipment?->model_text ?? '' }}</p>
            <p style="margin:0;"><strong>Paso actual:</strong> {{ $service->currentStep?->name ?? '—' }}</p>
        </div>

        <button type="button" class="btn" onclick="window.print()" style="margin-top:22px; width:auto; padding-left:24px; padding-right:24px;">Imprimir / Guardar como PDF</button>
    </div>

    <style>
        @media print {
            body { background:#fff; }
            .wrap { padding:0; align-items:flex-start; }
            .card { box-shadow:none; border:none; max-width:none; width:100%; }
            .btn { display:none !important; }
        }
    </style>

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 400);
        });
    </script>
@endsection
