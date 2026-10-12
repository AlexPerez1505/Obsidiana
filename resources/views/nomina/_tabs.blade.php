@include('asistencia._kit')
<nav class="rh-tabs rh-in" aria-label="Secciones de nómina" style="margin-bottom:16px;">
    <a class="rh-tab {{ request()->routeIs('nomina.index') ? 'on' : '' }}" href="{{ route('nomina.index') }}">Quincena</a>
    <a class="rh-tab {{ request()->routeIs('nomina.historial') ? 'on' : '' }}" href="{{ route('nomina.historial') }}">Historial</a>
    <a class="rh-tab {{ request()->routeIs('nomina.empleados') ? 'on' : '' }}" href="{{ route('nomina.empleados') }}">Empleados y sueldos</a>
    <a class="rh-tab {{ request()->routeIs('nomina.reglas') ? 'on' : '' }}" href="{{ route('nomina.reglas') }}">Reglas de bonos y descuentos</a>
</nav>
@if (session('status'))<x-ui.alert type="ok" style="margin-bottom:14px;">{{ session('status') }}</x-ui.alert>@endif
@if ($errors->any())<x-ui.alert type="err" style="margin-bottom:14px;">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</x-ui.alert>@endif
