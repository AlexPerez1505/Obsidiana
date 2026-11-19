<?php

namespace App\Services\Inventario;

use App\Models\PiezaComponente;
use App\Models\ProductoSerial;
use App\Models\User;
use App\Support\EdicionPropia;
use App\Support\SeriesUnicas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Lo que trae dentro cada pieza: un set completo desde la entrada, o partes
 * que se agregan después ("primero el procesador y la fuente, a la semana los
 * pigtails"). Cada componente con su serie y sus fotos.
 */
class ComponentesDePieza
{
    public function __construct(private FotoPorPedazos $fotos) {}

    public static function reglas(bool $nuevo): array
    {
        return [
            'nombre' => [$nuevo ? 'required_without:lista' : 'required', 'nullable', 'string', 'max:120'],
            'lista' => ['nullable', 'string', 'max:3000'],
            'padre_id' => ['nullable', 'integer'],
            'no_serie' => ['nullable', 'string', 'max:120'],
            'estado' => ['required', Rule::in(array_keys(PiezaComponente::ESTADOS))],
            'notas' => ['nullable', 'string', 'max:2000'],
            'fotos' => ['nullable', 'array', 'max:40'],
            'fotos.*' => ['string', 'max:255'],
            'quitar_fotos' => ['nullable', 'array'],
            'quitar_fotos.*' => ['string', 'max:255'],
        ];
    }

    public const MENSAJES = [
        'nombre.required' => 'Escribe qué componente es (p. ej. Pigtail, Interfaz, Fuente).',
        'nombre.required_without' => 'Escribe qué componente es (p. ej. Pigtail, Interfaz, Fuente).',
        'estado.required' => 'Indica si el componente ya llegó o está por llegar.',
    ];

    /** Los componentes de la pieza, ordenados como árbol, listos para la pantalla. */
    public function listar(ProductoSerial $pieza, ?User $user): array
    {
        $todos = $pieza->componentes()->with(['capturadoPor:id,name', 'recibidoPor:id,name'])->get();

        $porPadre = $todos->groupBy(fn ($c) => (int) $c->padre_id);
        $salida = [];
        $recorrer = function (int $padre, int $nivel) use (&$recorrer, &$salida, $porPadre, $user) {
            foreach ($porPadre->get($padre, collect()) as $c) {
                $salida[] = $this->fila($c, $nivel, $user);
                $recorrer((int) $c->id, $nivel + 1);
            }
        };
        $recorrer(0, 0);

        // Por si alguno quedó con un padre que ya no está.
        $vistos = collect($salida)->pluck('id');
        foreach ($todos->whereNotIn('id', $vistos) as $c) {
            $salida[] = $this->fila($c, 0, $user);
        }

        return [
            'componentes' => $salida,
            'total' => $todos->count(),
            'por_llegar' => $todos->where('estado', 'por_llegar')->count(),
            'puede_editar' => (bool) $user?->can('inventario.registrar'),
            'sugerencias' => PiezaComponente::query()->select('nombre')->distinct()->orderBy('nombre')->limit(200)->pluck('nombre'),
        ];
    }

    private function fila(PiezaComponente $c, int $nivel, ?User $user): array
    {
        return [
            'id' => $c->id,
            'padre_id' => $c->padre_id,
            'nivel' => $nivel,
            'nombre' => $c->nombre,
            'no_serie' => $c->no_serie,
            'estado' => $c->estado,
            'estado_label' => PiezaComponente::ESTADOS[$c->estado] ?? $c->estado,
            'notas' => $c->notas,
            'fotos' => collect($c->fotos ?: [])->map(fn ($p) => ['path' => $p, 'url' => Storage::disk(config('filesystems.fotos_disk', 'public'))->url($p)])->values(),
            'capturado' => trim(($c->capturadoPor?->name ?? '').' · '.$c->created_at?->format('d/m/Y'), ' ·'),
            'recibido' => $c->recibido_en ? trim($c->recibido_en->format('d/m/Y').' · '.($c->recibidoPor?->name ?? ''), ' ·') : null,
            'puede_eliminar' => EdicionPropia::puedeEliminarComponente($user, $c),
        ];
    }

    /**
     * Agrega uno o varios componentes. Con "lista" (uno por renglón) se dan de
     * alta varios de golpe, sin serie: típico al registrar lo que viene en
     * camino.
     *
     * @return Collection<int, PiezaComponente>
     */
    public function agregar(ProductoSerial $pieza, array $data, User $user): Collection
    {
        $padre = $this->padre($pieza, $data['padre_id'] ?? null);
        $nombres = collect(preg_split('/\r\n|\r|\n|,/', (string) ($data['lista'] ?? '')))
            ->map(fn ($n) => trim($n))->filter()->values();

        if ($nombres->isEmpty()) {
            $nombres = collect([trim((string) $data['nombre'])]);
        } elseif (filled($data['no_serie'] ?? null) && $nombres->count() > 1) {
            throw ValidationException::withMessages(['no_serie' => 'Para varios componentes a la vez no se pone serie: agrégala después en cada uno.']);
        }

        $serie = SeriesUnicas::normalizar($data['no_serie'] ?? null);
        $this->exigirSerieLibre($serie);
        $fotos = $this->fotosNuevas($data['fotos'] ?? []);
        $recibido = $data['estado'] === 'recibido';

        return DB::transaction(fn () => $nombres->map(fn (string $nombre, int $i) => PiezaComponente::create([
            'producto_serial_id' => $pieza->id,
            'padre_id' => $padre?->id,
            'nombre' => mb_strtoupper(mb_substr($nombre, 0, 120)),
            'no_serie' => $i === 0 ? $serie : null,
            'estado' => $data['estado'],
            'recibido_en' => $recibido ? now() : null,
            'recibido_por' => $recibido ? $user->id : null,
            'fotos' => $i === 0 && $fotos ? $fotos : null,
            'notas' => $i === 0 ? ($data['notas'] ?? null) : null,
            'capturado_por' => $user->id,
        ])));
    }

    /** Corrige un componente o lo marca como recibido cuando por fin llega. */
    public function actualizar(PiezaComponente $c, array $data, User $user): PiezaComponente
    {
        $padre = $this->padre($c->pieza, $data['padre_id'] ?? null);
        if ($padre && $this->esDescendiente($c, $padre)) {
            throw ValidationException::withMessages(['padre_id' => 'Un componente no puede ir dentro de sí mismo ni de lo que trae dentro.']);
        }

        $serie = SeriesUnicas::normalizar($data['no_serie'] ?? null);
        $this->exigirSerieLibre($serie, $c->id);

        $quitar = collect($data['quitar_fotos'] ?? []);
        $antes = $c->fotos ?: [];
        $fotos = collect($antes)->reject(fn ($p) => $quitar->contains($p))
            ->merge($this->fotosNuevas($data['fotos'] ?? []))->values()->all();

        $llega = $c->estado === 'por_llegar' && $data['estado'] === 'recibido';
        $c->fill([
            'padre_id' => $padre?->id,
            'nombre' => mb_strtoupper(trim((string) $data['nombre'])),
            'no_serie' => $serie,
            'estado' => $data['estado'],
            'notas' => $data['notas'] ?? null,
            'fotos' => $fotos ?: null,
        ]);
        if ($llega) {
            $c->recibido_en = now();
            $c->recibido_por = $user->id;
        } elseif ($data['estado'] === 'por_llegar') {
            $c->recibido_en = null;
            $c->recibido_por = null;
        }
        $c->save();

        // Las fotos quitadas se borran del disco (solo las de este componente).
        $disco = Storage::disk(config('filesystems.fotos_disk', 'public'));
        foreach ($quitar->intersect($antes) as $p) {
            $disco->delete($p);
        }

        return $c;
    }

    private function padre(ProductoSerial $pieza, $padreId): ?PiezaComponente
    {
        if (! $padreId) {
            return null;
        }

        $padre = PiezaComponente::where('producto_serial_id', $pieza->id)->find((int) $padreId);
        if (! $padre) {
            throw ValidationException::withMessages(['padre_id' => 'Ese componente no es de esta pieza.']);
        }

        return $padre;
    }

    private function esDescendiente(PiezaComponente $c, PiezaComponente $candidato): bool
    {
        for ($n = $candidato, $vueltas = 0; $n && $vueltas < 50; $n = $n->padre, $vueltas++) {
            if ($n->id === $c->id) {
                return true;
            }
        }

        return false;
    }

    /** La serie no puede repetirse ni con otro componente ni con una pieza del inventario. */
    private function exigirSerieLibre(?string $serie, ?int $ignorar = null): void
    {
        if (! $serie) {
            return;
        }

        $otro = PiezaComponente::query()
            ->with('pieza:id,codigo')
            ->when($ignorar, fn ($q) => $q->where('id', '!=', $ignorar))
            ->whereRaw('UPPER(TRIM(no_serie)) = ?', [$serie])
            ->first();
        if ($otro) {
            throw ValidationException::withMessages(['no_serie' => "La serie {$serie} ya la tiene el componente {$otro->nombre}".($otro->pieza?->codigo ? " de la pieza {$otro->pieza->codigo}" : '').'.']);
        }

        $usada = SeriesUnicas::enUso([$serie]);
        if ($usada->isNotEmpty()) {
            throw ValidationException::withMessages(['no_serie' => SeriesUnicas::mensaje($usada)]);
        }
    }

    /** Solo se aceptan fotos ya armadas por la subida por pedazos. */
    private function fotosNuevas(array $rutas): array
    {
        return collect($rutas)->map(fn ($p) => $this->fotos->resolver((string) $p))->filter()->unique()->values()->all();
    }
}
