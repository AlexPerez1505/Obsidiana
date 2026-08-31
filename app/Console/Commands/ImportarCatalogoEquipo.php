<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\EquipmentModel;
use App\Models\EquipmentType;
use App\Models\Subtype;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Importa al catalogo las listas que hoy viven escritas en un archivo JS.
 *
 * Espera un archivo con los dos objetos del formulario viejo:
 *
 *   const tiposEquipos = { 'endoscopia': ["Gastroscopio", ...], ... };
 *   const marcasModelosPorSubtipo = { endoscopia: { 'gastroscopio': { 'Olympus': ["GIF-H190", ...] } } };
 *
 * Uso:
 *   php artisan catalogo:importar storage/app/catalogo.js
 *   php artisan catalogo:importar storage/app/catalogo.js --dry-run
 */
class ImportarCatalogoEquipo extends Command
{
    protected $signature = 'catalogo:importar
                            {archivo : Ruta del archivo .js o .json con las listas}
                            {--dry-run : Solo muestra lo que se importaria, sin escribir nada}';

    protected $description = 'Importa tipos, subtipos, marcas y modelos al catálogo de equipo';

    private array $contadores = [
        'tipos' => 0,
        'subtipos' => 0,
        'marcas' => 0,
        'modelos' => 0,
    ];

    public function handle(): int
    {
        $ruta = $this->argument('archivo');

        if (! is_file($ruta)) {
            $this->error("No encontré el archivo: {$ruta}");

            return self::FAILURE;
        }

        $contenido = file_get_contents($ruta);

        $tipos = $this->extraerObjeto($contenido, 'tiposEquipos');
        $marcas = $this->extraerObjeto($contenido, 'marcasModelosPorSubtipo');

        if ($tipos === null && $marcas === null) {
            $this->error('No encontré ni tiposEquipos ni marcasModelosPorSubtipo en el archivo.');

            return self::FAILURE;
        }

        $seco = (bool) $this->option('dry-run');

        if ($seco) {
            $this->warn('Modo prueba: no se va a escribir nada en la base.');
        }

        $trabajo = function () use ($tipos, $marcas, $seco) {
            if ($tipos) {
                $this->importarTiposYSubtipos($tipos, $seco);
            }

            if ($marcas) {
                $this->importarMarcasYModelos($marcas, $seco);
            }
        };

        if ($seco) {
            // Se ejecuta dentro de una transaccion que siempre se revierte,
            // asi el conteo es real pero no queda nada guardado.
            DB::beginTransaction();
            $trabajo();
            DB::rollBack();
        } else {
            DB::transaction($trabajo);
        }

        $this->newLine();
        $this->table(
            ['Tipos', 'Subtipos', 'Marcas', 'Modelos'],
            [[$this->contadores['tipos'], $this->contadores['subtipos'], $this->contadores['marcas'], $this->contadores['modelos']]]
        );
        $this->info($seco ? 'Prueba terminada, nada se guardó.' : 'Catálogo importado.');

        return self::SUCCESS;
    }

    // ===================== Importación =====================

    private function importarTiposYSubtipos(array $tipos, bool $seco): void
    {
        foreach ($tipos as $nombreTipo => $subtipos) {
            $tipo = $this->tipo($nombreTipo);

            foreach ((array) $subtipos as $nombreSubtipo) {
                $this->subtipo($tipo, (string) $nombreSubtipo);
            }
        }
    }

    private function importarMarcasYModelos(array $arbol, bool $seco): void
    {
        foreach ($arbol as $nombreTipo => $subtipos) {
            $tipo = $this->tipo((string) $nombreTipo);

            foreach ((array) $subtipos as $nombreSubtipo => $marcas) {
                // El archivo escribe el mismo subtipo con distinta capitalizacion
                // segun el bloque ('procesador' vs 'Procesador'), asi que se
                // busca sin distinguir mayusculas ni acentos.
                $subtipo = $this->subtipo($tipo, (string) $nombreSubtipo);

                foreach ((array) $marcas as $nombreMarca => $modelos) {
                    $marca = $this->marca((string) $nombreMarca);

                    if (! $subtipo->brands()->where('brands.id', $marca->id)->exists()) {
                        $subtipo->brands()->attach($marca->id);
                        $this->contadores['marcas']++;
                    }

                    foreach ((array) $modelos as $nombreModelo) {
                        $this->modelo($subtipo, $marca, (string) $nombreModelo);
                    }
                }
            }
        }
    }

    private function tipo(string $nombre): EquipmentType
    {
        $nombre = $this->limpiar($nombre);

        $existente = EquipmentType::all()->first(fn ($t) => $this->igual($t->name, $nombre));

        if ($existente) {
            return $existente;
        }

        $this->contadores['tipos']++;

        return EquipmentType::create(['name' => $this->comoTitulo($nombre)]);
    }

    private function subtipo(EquipmentType $tipo, string $nombre): Subtype
    {
        $nombre = $this->limpiar($nombre);

        $existente = $tipo->subtypes()->get()->first(fn ($s) => $this->igual($s->name, $nombre));

        if ($existente) {
            return $existente;
        }

        $this->contadores['subtipos']++;

        return Subtype::create([
            'equipment_type_id' => $tipo->id,
            'name' => $this->comoTitulo($nombre),
        ]);
    }

    private function marca(string $nombre): Brand
    {
        $nombre = $this->limpiar($nombre);

        $existente = Brand::all()->first(fn ($b) => $this->igual($b->name, $nombre));

        if ($existente) {
            return $existente;
        }

        return Brand::create(['name' => $nombre]);
    }

    private function modelo(Subtype $subtipo, Brand $marca, string $nombre): void
    {
        $nombre = $this->limpiar($nombre);

        if ($nombre === '' || $nombre === '/') {
            return;
        }

        $existe = EquipmentModel::where('subtype_id', $subtipo->id)
            ->where('brand_id', $marca->id)
            ->where('name', $nombre)
            ->exists();

        if ($existe) {
            return;
        }

        EquipmentModel::create([
            'subtype_id' => $subtipo->id,
            'brand_id' => $marca->id,
            'name' => $nombre,
        ]);

        $this->contadores['modelos']++;
    }

    // ===================== Utilidades =====================

    private function limpiar(string $texto): string
    {
        return trim(preg_replace('/\s+/u', ' ', $texto));
    }

    /** Compara ignorando mayusculas y acentos. */
    private function igual(?string $a, ?string $b): bool
    {
        return $this->clave((string) $a) === $this->clave((string) $b);
    }

    private function clave(string $texto): string
    {
        $texto = mb_strtolower($this->limpiar($texto), 'UTF-8');

        return strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ü' => 'u', 'ñ' => 'n',
        ]);
    }

    /** Primera letra en mayuscula, respetando los nombres que ya vienen bien. */
    private function comoTitulo(string $texto): string
    {
        if ($texto !== mb_strtolower($texto, 'UTF-8')) {
            return $texto;
        }

        return mb_strtoupper(mb_substr($texto, 0, 1), 'UTF-8') . mb_substr($texto, 1);
    }

    // ===================== Lectura del archivo JS =====================

    /**
     * Saca un objeto por nombre y lo convierte a arreglo.
     *
     * El archivo es JavaScript, no JSON: trae comillas simples, comas
     * sobrantes, comentarios y llaves sin comillas. Se normaliza recorriendo
     * caracter por caracter para no romper lo que va dentro de las cadenas.
     */
    private function extraerObjeto(string $contenido, string $nombre): ?array
    {
        $inicio = strpos($contenido, $nombre);

        if ($inicio === false) {
            return null;
        }

        $llaveInicial = strpos($contenido, '{', $inicio);

        if ($llaveInicial === false) {
            return null;
        }

        $bruto = $this->recortarObjeto($contenido, $llaveInicial);

        if ($bruto === null) {
            $this->error("El objeto {$nombre} está incompleto: no encontré su llave de cierre.");

            return null;
        }

        $json = $this->jsAJson($bruto);
        $datos = json_decode($json, true);

        if (! is_array($datos)) {
            $this->error("No pude interpretar {$nombre}: " . json_last_error_msg());

            return null;
        }

        return $datos;
    }

    /** Devuelve el texto del objeto, balanceando llaves fuera de cadenas. */
    private function recortarObjeto(string $texto, int $desde): ?string
    {
        $profundidad = 0;
        $enCadena = false;
        $comilla = '';
        $largo = strlen($texto);

        for ($i = $desde; $i < $largo; $i++) {
            $c = $texto[$i];

            if ($enCadena) {
                if ($c === '\\') {
                    $i++;
                } elseif ($c === $comilla) {
                    $enCadena = false;
                }

                continue;
            }

            if ($c === '"' || $c === "'") {
                $enCadena = true;
                $comilla = $c;
            } elseif ($c === '{') {
                $profundidad++;
            } elseif ($c === '}') {
                $profundidad--;

                if ($profundidad === 0) {
                    return substr($texto, $desde, $i - $desde + 1);
                }
            }
        }

        return null;
    }

    private function jsAJson(string $js): string
    {
        $salida = '';
        $largo = strlen($js);
        $i = 0;

        while ($i < $largo) {
            $c = $js[$i];

            // Comentarios, solo fuera de cadenas
            if ($c === '/' && $i + 1 < $largo && $js[$i + 1] === '/') {
                while ($i < $largo && $js[$i] !== "\n") {
                    $i++;
                }

                continue;
            }

            if ($c === '/' && $i + 1 < $largo && $js[$i + 1] === '*') {
                $fin = strpos($js, '*/', $i);
                $i = $fin === false ? $largo : $fin + 2;

                continue;
            }

            // Cadenas con comilla simple -> comilla doble
            if ($c === "'") {
                $salida .= '"';
                $i++;

                while ($i < $largo && $js[$i] !== "'") {
                    if ($js[$i] === '\\') {
                        $salida .= $js[$i] . ($js[$i + 1] ?? '');
                        $i += 2;

                        continue;
                    }

                    // Una comilla doble dentro pasa a ir escapada
                    $salida .= $js[$i] === '"' ? '\\"' : $js[$i];
                    $i++;
                }

                $salida .= '"';
                $i++;

                continue;
            }

            // Cadenas con comilla doble: se copian tal cual
            if ($c === '"') {
                $salida .= $c;
                $i++;

                while ($i < $largo && $js[$i] !== '"') {
                    if ($js[$i] === '\\') {
                        $salida .= $js[$i] . ($js[$i + 1] ?? '');
                        $i += 2;

                        continue;
                    }

                    $salida .= $js[$i];
                    $i++;
                }

                $salida .= '"';
                $i++;

                continue;
            }

            $salida .= $c;
            $i++;
        }

        // Llaves sin comillas: { endoscopia: ... } -> { "endoscopia": ... }
        $salida = preg_replace('/([{,]\s*)([A-Za-z_$][A-Za-z0-9_$]*)(\s*:)/u', '$1"$2"$3', $salida);

        // Comas sobrantes antes de cerrar
        $salida = preg_replace('/,(\s*[}\]])/u', '$1', $salida);

        return $salida;
    }
}
