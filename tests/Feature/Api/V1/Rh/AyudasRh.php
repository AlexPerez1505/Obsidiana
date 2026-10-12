<?php

namespace Tests\Feature\Api\V1\Rh;

use App\Models\AttendancePunch;
use App\Models\EmployeeSchedule;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Lo que repiten las pruebas de RH: empleados con horario y el reloj
 * puesto en hora local (la app corre en UTC; las reglas, en México).
 */
trait AyudasRh
{
    /** El "ahora" de la prueba, dado en hora local. */
    protected function ahoraLocal(string $fechaHora): void
    {
        $this->travelTo(Carbon::parse($fechaHora, 'America/Mexico_City'));
    }

    /** Empleado aprobado que checa: lunes a viernes 09:00–18:00, 5 min de tolerancia. */
    protected function empleado(array $atributos = [], array $horario = []): User
    {
        $u = $this->usuarioApi(atributos: $atributos);

        EmployeeSchedule::create($horario + [
            'user_id' => $u->id, 'weekdays' => [1, 2, 3, 4, 5],
            'time_in' => '09:00', 'time_out' => '18:00', 'tolerance_minutes' => 5, 'active' => true,
        ]);

        return $u->fresh();
    }

    /** Checada válida directa en la base (fecha y hora locales), sin pasar por la API. */
    protected function checada(User $u, string $fechaHoraLocal, array $extra = []): AttendancePunch
    {
        $cuando = Carbon::parse($fechaHoraLocal, 'America/Mexico_City');

        return AttendancePunch::create($extra + [
            'uuid' => (string) Str::uuid(), 'user_id' => $u->id, 'punched_at' => $cuando->copy()->utc(),
            'method' => 'manual', 'kind' => 'entrada', 'seq' => 1, 'work_date' => $cuando->toDateString(),
        ]);
    }

    /** days[1..5] de 09:00 a 18:00, como lo manda la pantalla. */
    protected function semanaNormal(): array
    {
        return collect(range(1, 5))->mapWithKeys(fn ($n) => [$n => ['on' => true, 'in' => '09:00', 'out' => '18:00']])->all();
    }
}
