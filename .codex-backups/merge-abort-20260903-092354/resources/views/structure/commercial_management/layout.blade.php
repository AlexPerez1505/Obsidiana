@extends('layouts.dashboard')

{{--
    Layout del modulo comercial.

    Antes traia ~500 lineas de CSS propio (degradados, bordes de neon y
    sombras de color) que se salian del sistema de diseno. Ese CSS lo usaba
    una sola vista, que ya fue migrada, asi que este layout ahora solo
    encadena con layouts.dashboard: los estilos salen del sistema comun.
--}}

@section('title')
    @yield('title', 'Módulo Comercial')
@endsection

@section('content')
    @yield('commercial_content')
@endsection
