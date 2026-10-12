{{-- Rejilla de horario por día. Recibe $h (EmployeeSchedule|null), $base y $dias --}}
@php
    $dias = \App\Models\EmployeeSchedule::DIAS;
    $general = ['in' => $h ? $h->entradaHHMM() : $base['time_in'], 'out' => $h ? $h->salidaHHMM() : $base['time_out']];
@endphp
<div class="hg">
    @foreach ($dias as $n => $txt)
        @php
            $activo = $h ? in_array($n, array_map('intval', $h->weekdays ?? []), true) : in_array($n, $base['weekdays'], true);
            $horas = $h ? $h->horasDelDia($n) : $general;
        @endphp
        <div class="hg-d {{ $activo ? 'on' : '' }}">
            <label class="hg-t"><input type="checkbox" name="days[{{ $n }}][on]" value="1" @checked($activo)><span>{{ $txt }}</span></label>
            <input type="time" name="days[{{ $n }}][in]" value="{{ $horas['in'] }}" title="Entrada del {{ $txt }}">
            <input type="time" name="days[{{ $n }}][out]" value="{{ $horas['out'] }}" title="Salida del {{ $txt }}">
        </div>
    @endforeach
</div>
