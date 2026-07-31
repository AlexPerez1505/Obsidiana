@extends('layouts.dashboard')

@section('title', 'Crear Categoría')

@section('content')
    <div style="max-width:520px; margin:0 auto;">
        <h2 class="page-title" style="margin:0 0 8px;">Nueva Categoría</h2>
        <p class="page-sub" style="margin:0 0 22px;">Registra una categoría con solo Nombre. El Id se genera automáticamente.</p>

        @if (session('status'))
            <div class="alert alert--ok" style="margin-bottom:16px;">
                {{ session('status') }}
            </div>
        @endif

        <div class="card" style="background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:22px; box-shadow:var(--shadow);">
            <form method="POST" action="{{ route('configuracion.categorias.store') }}">
                @csrf

                <label for="name">Nombre</label>
                <input id="name" name="name" type="text" placeholder="Ingrese el nombre de la categoría" value="{{ old('name') }}" required>
                @error('name')
                    <div class="err">{{ $message }}</div>
                @enderror

                <div style="display:flex; justify-content:flex-end; margin-top:18px;">
                    <button type="submit" class="btn">Guardar Categoría</button>
                </div>
            </form>
        </div>
    </div>
@endsection
