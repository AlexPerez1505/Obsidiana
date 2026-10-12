@include('asistencia._kit')
<nav class="rh-tabs rh-in" aria-label="Secciones de asistencia" style="margin-bottom:16px;">
    @can('asistencia.ver')
        <a class="rh-tab {{ request()->routeIs('asistencia.panel', 'asistencia.empleado') ? 'on' : '' }}" href="{{ route('asistencia.panel') }}">Panel</a>
        <a class="rh-tab {{ request()->routeIs('asistencia.checadas') ? 'on' : '' }}" href="{{ route('asistencia.checadas') }}">Checadas</a>
        <a class="rh-tab {{ request()->routeIs('asistencia.modalidades') ? 'on' : '' }}" href="{{ route('asistencia.modalidades') }}">Incidencias del día</a>
    @endcan
    @can('asistencia.gestionar')
        <a class="rh-tab {{ request()->routeIs('asistencia.horarios') ? 'on' : '' }}" href="{{ route('asistencia.horarios') }}">Horarios y festivos</a>
        <a class="rh-tab {{ request()->routeIs('asistencia.eventos') ? 'on' : '' }}" href="{{ route('asistencia.eventos') }}">Eventos de empresa</a>
    @endcan
    @can('asistencia.biometria')
        <a class="rh-tab {{ request()->routeIs('asistencia.biometria.*') ? 'on' : '' }}" href="{{ route('asistencia.biometria.index') }}">Lectores y huellas</a>
    @endcan
    @can('asistencia.nomina')
        <a class="rh-tab" href="{{ route('nomina.index') }}">Nómina <x-ui.icon name="arrow-right" :size="14" /></a>
    @endcan
</nav>
@if (session('status'))<x-ui.alert type="ok" style="margin-bottom:14px;">{{ session('status') }}</x-ui.alert>@endif
@if ($errors->any())<x-ui.alert type="err" style="margin-bottom:14px;">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</x-ui.alert>@endif
