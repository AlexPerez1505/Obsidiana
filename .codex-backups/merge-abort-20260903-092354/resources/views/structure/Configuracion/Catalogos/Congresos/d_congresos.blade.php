@extends('structure.Configuracion.layout')

@section('title', 'Eliminar Congreso')
@section('page-title', 'Eliminar Congreso')

@section('configuracion_content')
    <div class="modal-overlay">
        <div class="modal-card catalog-card">
            {{-- Este parrafo no es un subtitulo decorativo: es la pregunta de confirmacion --}}
            <p class="page-sub">¿Estás seguro de que deseas eliminar el congreso <strong style="color:#00A8FF;">{{ $congress->nombre }}</strong>? Esta acción no se puede deshacer.</p>

            @if (session('status'))
                <div class="alert alert--ok" style="margin:16px 0 0;">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('configuracion.congresos.destroy', $congress) }}" style="margin-top:18px;">
                @csrf
                @method('DELETE')

                <div class="form-actions">
                    <a href="{{ route('configuracion.catalogos.index') }}" class="modal-back" aria-label="Regresar">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M15 18l-6-6 6-6"/>
                        </svg>
                        <span>Cancelar</span>
                    </a>
                    <button type="submit" class="btn" style="background:#ef4444; color:#fff;">Eliminar</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .modal-overlay {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            padding: 22px;
        }
        .modal-card {
            width: 100%;
            max-width: 520px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }
        .modal-header .page-title { margin: 0; flex: 1; }
        .modal-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border: 1px solid rgba(0, 168, 255, 0.55);
            border-radius: 10px;
            background: rgba(8, 18, 40, 0.45);
            color: #00A8FF;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background .16s ease, border-color .16s ease;
        }
        .modal-back:hover {
            background: rgba(0, 168, 255, 0.14);
            border-color: #00A8FF;
        }
        .modal-back svg { width: 16px; height: 16px; }
        .form-actions { display:flex; justify-content:flex-end; align-items:center; gap:10px; margin-top:18px; }
        :root[data-theme="light"] .modal-overlay { background: rgba(15, 23, 42, 0.35); }
        :root[data-theme="light"] .modal-back {
            background: rgba(15, 23, 42, 0.04);
            border-color: rgba(15, 23, 42, 0.14);
            color: var(--primary);
        }
        :root[data-theme="light"] .modal-back:hover {
            background: var(--primary-soft);
            border-color: var(--primary);
        }
        @media (max-width: 640px) {
            .modal-overlay { padding: 12px; }
            .modal-card { width: 100%; max-width: none; }
            .form-actions { flex-direction: column; align-items: stretch; gap: 8px; }
            .form-actions > * { width: 100%; justify-content: center; }
        }
    </style>
@endsection