<?php

namespace App\Services\Almacen;

use App\Models\PiezaProceso;
use App\Models\ProductoSerial;
use App\Services\RutaDeProcesos;
use App\Support\ChecklistProceso;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Las colas de hojalatería, mantenimiento y limpieza, y cómo se cierra un
 * paso con su evidencia.
 *
 * La ruta de cada pieza la manda RutaDeProcesos; esto es lo que ve y hace
 * quien trabaja las piezas, igual desde la web que desde la API.
 */
class ColasDeProcesos
{
    /** Desde cuántos días parada se considera que una pieza se atoró. */
    public const DIAS_ATORADA = 7;

    public const REGLAS_CIERRE = [
        // Nullable a propósito: si no viene nada, el mensaje lo da la
        // verificación, que dice exactamente qué falta, y no un "el campo
        // checklist es obligatorio" que no ayuda.
        'checklist' => ['nullable', 'array'],
        'trabajo_realizado' => ['nullable', 'string', 'max:1000'],
        'evidencias' => ['nullable', 'array', 'max:5'],
        'evidencias.*' => ['image', 'max:5120'],
    ];

    public const MENSAJES_CIERRE = [
        'evidencias.*.image' => 'La evidencia tiene que ser una foto.',
        'evidencias.max' => 'Puedes subir máximo 5 fotos.',
    ];

    public const REGLAS_OMITIR = [
        'motivo' => ['required', 'string', 'min:10', 'max:1000'],
    ];

    public const MENSAJES_OMITIR = [
        'motivo.required' => 'Escribe por qué no hacía falta este proceso.',
        'motivo.min' => 'Explica un poco más: esto queda como constancia de la decisión.',
    ];

    public function __construct(private readonly RutaDeProcesos $ruta)
    {
    }

    /**
     * Los pasos que están en cola: una pieza aparece solo en el proceso
     * donde está parada, no en todos los que le faltan.
     */
    public function pasosEnCola(): Builder
    {
        return PiezaProceso::query()
            ->whereIn('estado', ['pendiente', 'en_curso'])
            ->whereHas('pieza', fn ($q) => $q->whereColumn('producto_seriales.estado', 'pieza_procesos.proceso'))
            ->orderBy('estado')          // en_curso primero
            ->orderBy('created_at');
    }

    /** Lo que ya salió de sus procesos y espera a que alguien lo note. */
    public function listas(int $cuantas = 12): Collection
    {
        return ProductoSerial::query()
            ->where('estado', 'disponible')
            ->where('vendido', false)
            ->whereHas('procesos', fn ($q) => $q->where('estado', 'terminado'))
            ->with('producto')
            ->latest('updated_at')
            ->limit($cuantas)
            ->get();
    }

    /**
     * Los números de arriba de la pantalla de colas.
     *
     * @return array{en_cola: int, en_curso: int, atoradas: int, listas: int}
     */
    public function resumen(Collection $pasos, Collection $listas): array
    {
        return [
            'en_cola' => $pasos->where('estado', 'pendiente')->count(),
            'en_curso' => $pasos->where('estado', 'en_curso')->count(),
            'atoradas' => $pasos->filter(fn (PiezaProceso $p) => $p->diasDetenida() >= self::DIAS_ATORADA)->count(),
            'listas' => $listas->count(),
        ];
    }

    /**
     * Cierra el paso con su verificación y sus fotos.
     *
     * Devuelve las razones por las que NO se cerró; vacío significa que se
     * cerró. Se revisa antes de subir nada para no dejar archivos huérfanos
     * en el disco, y si algo cambia entre la revisión y el guardado, se
     * borra lo que se alcanzó a subir.
     *
     * @param  array<int, UploadedFile>  $fotos
     * @return array<int, string>
     */
    public function cerrar(PiezaProceso $paso, ?array $checklistRecibido, array $fotos, ?string $trabajoRealizado): array
    {
        $checklist = ChecklistProceso::limpiar($paso, $checklistRecibido ?? []);
        $yaTenia = count($paso->evidencias ?? []);

        $razones = ChecklistProceso::razonesParaNoCerrar($paso, $checklist, $yaTenia + count($fotos));

        if ($razones) {
            return $razones;
        }

        $disco = config('filesystems.fotos_disk', 'public');

        $subidas = collect($fotos)
            ->map(fn (UploadedFile $archivo) => $archivo->store('inventario/procesos', $disco))
            ->all();

        try {
            $this->ruta->terminar($paso, $checklist, $subidas, $trabajoRealizado);
        } catch (\RuntimeException $e) {
            Storage::disk($disco)->delete($subidas);

            return [$e->getMessage()];
        }

        return [];
    }

    public static function reglasAgregar(): array
    {
        return [
            'proceso' => ['required', Rule::in(array_keys(PiezaProceso::PROCESOS))],
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }

    public static function reglasDefinir(): array
    {
        return [
            'procesos' => ['nullable', 'array'],
            'procesos.*' => [Rule::in(array_keys(PiezaProceso::PROCESOS))],
        ];
    }

    /** Manda una pieza a un proceso que no traía en su ruta. */
    public function agregar(ProductoSerial $pieza, string $proceso, ?string $motivo = null): void
    {
        $this->ruta->agregar($pieza, $proceso, $motivo ?: 'Agregado a mano');
    }

    /** Una pieza vendida o dada de baja ya no la manda la ruta. */
    public function razonParaNoRedefinir(ProductoSerial $pieza): ?string
    {
        return $pieza->vendido || $pieza->estado === 'baja'
            ? 'No se pueden cambiar los procesos: la pieza ya se vendió o se dio de baja.'
            : null;
    }

    /**
     * Corrige de un jalón toda la ruta de la pieza (p. ej. cuando en la
     * entrada se marcaron de menos). Lo ya hecho se conserva; ver
     * RutaDeProcesos::definir.
     *
     * @param  array<int, string>  $procesos
     */
    public function redefinir(ProductoSerial $pieza, array $procesos): void
    {
        $motivos = collect($procesos)
            ->mapWithKeys(fn ($p) => [$p => 'Corrección de la ruta tras la entrada'])
            ->all();

        $this->ruta->definir($pieza, $procesos, $motivos);
    }

    /**
     * Lo que dice la pantalla del paso: solo se toma una pieza que nadie
     * está trabajando, y solo se cierra o descarta la que está en curso.
     * La web lo resuelve escondiendo botones; la API lo tiene que decir.
     */
    public function razonParaNoIniciar(PiezaProceso $paso): ?string
    {
        return match ($paso->estado) {
            'en_curso' => 'Este proceso ya está en curso.',
            'terminado', 'omitido' => 'Este proceso ya se cerró.',
            default => null,
        };
    }

    public function razonParaNoCerrar(PiezaProceso $paso): ?string
    {
        return match ($paso->estado) {
            'pendiente' => 'Primero toma la pieza (iniciar): hasta entonces no se puede cerrar.',
            'terminado', 'omitido' => 'Este proceso ya se cerró.',
            default => null,
        };
    }
}
