@extends('structure.Configuracion.layout')

@section('title', 'Crear Categoría')
@section('page-title', 'Nueva Categoría')

@section('configuracion_content')
    <form method="POST" action="{{ route('configuracion.categorias.store') }}" class="cat-form">
        @csrf

        <x-ui.card>
            <x-ui.section-title style="margin:0 0 4px;">Datos de la categoría</x-ui.section-title>
            <p class="muted" style="margin:0 0 18px; font-size:13.5px;">
                Las categorías sirven para clasificar clientes y congresos.
            </p>

            <x-ui.form-group label="Nombre" name="name"
                             placeholder="Ingrese el nombre de la categoría"
                             :required="true" :autofocus="true" />
        </x-ui.card>

        <div class="page-foot">
            <a href="{{ route('configuracion.catalogos.index') }}" class="btn btn--ghost">Regresar</a>
            <button type="submit" class="btn">Guardar categoría</button>
        </div>
    </form>

    <style>
        /* Un solo campo: la columna estrecha se lee mejor que un formulario a todo lo ancho. */
        .cat-form { max-width:560px; }
    </style>
@endsection
