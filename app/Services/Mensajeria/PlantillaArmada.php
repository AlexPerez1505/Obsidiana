<?php

namespace App\Services\Mensajeria;

/**
 * Una plantilla de WhatsApp tal como la regresa Meta (components) y lo que
 * hace falta para mandarla: qué variables pide, cómo se ve ya llena y los
 * "components" con los parámetros para la API.
 *
 * Soporta variables por posición ({{1}}) y por nombre ({{nombre}}), en el
 * encabezado de texto, el cuerpo y los botones de URL dinámica y de copiar
 * código. El encabezado puede ser imagen, video o documento.
 */
class PlantillaArmada
{
    private const VAR = '/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/';

    public function __construct(private readonly array $p) {}

    private function parte(string $tipo): ?array
    {
        foreach ((array) ($this->p['components'] ?? []) as $c) {
            if (strtoupper((string) ($c['type'] ?? '')) === $tipo) {
                return $c;
            }
        }

        return null;
    }

    private function nombrada(): bool
    {
        return strtoupper((string) ($this->p['parameter_format'] ?? 'POSITIONAL')) === 'NAMED';
    }

    private static function variables(?string $texto): array
    {
        preg_match_all(self::VAR, (string) $texto, $m);

        return array_values(array_unique($m[1]));
    }

    private static function llenar(?string $texto, array $vals): string
    {
        return (string) preg_replace_callback(self::VAR, fn ($m) => array_key_exists($m[1], $vals) && $vals[$m[1]] !== '' ? $vals[$m[1]] : '{{'.$m[1].'}}', (string) $texto);
    }

    /** Tipo de archivo que pide el encabezado (image, video, document) o null. */
    public function mediaEncabezado(): ?string
    {
        $h = $this->parte('HEADER');

        return match (strtoupper((string) ($h['format'] ?? ''))) {
            'IMAGE' => 'image', 'VIDEO' => 'video', 'DOCUMENT' => 'document', default => null,
        };
    }

    /**
     * Valida lo capturado y arma la vista.
     *
     * @return array{faltan: string[], media: ?string, vista: array}
     */
    public function armar(array $valores): array
    {
        $faltan = [];
        $h = $this->parte('HEADER');
        $b = $this->parte('BODY');
        $f = $this->parte('FOOTER');
        $btns = (array) ($this->parte('BUTTONS')['buttons'] ?? []);

        $hv = array_map(fn ($v) => trim((string) $v), (array) ($valores['header'] ?? []));
        $bv = array_map(fn ($v) => trim((string) $v), (array) ($valores['body'] ?? []));
        $btv = array_map(fn ($v) => trim((string) $v), (array) ($valores['botones'] ?? []));

        $formato = strtoupper((string) ($h['format'] ?? ''));
        if ($formato === 'TEXT') {
            foreach (self::variables($h['text'] ?? '') as $v) {
                if (($hv[$v] ?? '') === '') {
                    $faltan[] = 'encabezado {{'.$v.'}}';
                }
            }
        }
        foreach (self::variables($b['text'] ?? '') as $v) {
            if (($bv[$v] ?? '') === '') {
                $faltan[] = 'cuerpo {{'.$v.'}}';
            }
        }

        $botones = [];
        foreach ($btns as $i => $bt) {
            $tipo = strtoupper((string) ($bt['type'] ?? ''));
            $dinamico = ($tipo === 'URL' && self::variables($bt['url'] ?? '')) || $tipo === 'COPY_CODE';
            if ($dinamico && ($btv[$i] ?? '') === '') {
                $faltan[] = 'botón "'.($bt['text'] ?? ($i + 1)).'"';
            }
            $botones[] = [
                'tipo' => $tipo,
                'texto' => $bt['text'] ?? ($tipo === 'COPY_CODE' ? 'Copiar código' : 'Botón'),
                'url' => $tipo === 'URL' ? self::llenar($bt['url'] ?? '', self::variables($bt['url'] ?? '') ? [self::variables($bt['url'])[0] => $btv[$i] ?? ''] : []) : null,
                'telefono' => $bt['phone_number'] ?? null,
                'codigo' => $tipo === 'COPY_CODE' ? ($btv[$i] ?? null) : null,
            ];
        }

        return [
            'faltan' => $faltan,
            'media' => $this->mediaEncabezado(),
            'vista' => [
                'encabezado' => $formato === 'TEXT' ? self::llenar($h['text'] ?? '', $hv) : null,
                'encabezado_formato' => $formato ?: null,
                'cuerpo' => self::llenar($b['text'] ?? '', $bv),
                'pie' => $f['text'] ?? null,
                'botones' => $botones,
            ],
        ];
    }

    /** Los "components" con parámetros para POST /messages. */
    public function componentes(array $valores, ?string $mediaId, ?string $nombreArchivo): array
    {
        $out = [];
        $nombrada = $this->nombrada();
        $param = fn (string $clave, string $texto) => ['type' => 'text', 'text' => mb_substr($texto, 0, 1024)] + ($nombrada ? ['parameter_name' => $clave] : []);

        $h = $this->parte('HEADER');
        $formato = strtoupper((string) ($h['format'] ?? ''));
        if ($formato === 'TEXT' && ($vars = self::variables($h['text'] ?? ''))) {
            $out[] = ['type' => 'header', 'parameters' => array_map(fn ($v) => $param($v, (string) ($valores['header'][$v] ?? '')), $vars)];
        } elseif ($media = $this->mediaEncabezado()) {
            $obj = ['id' => $mediaId];
            if ($media === 'document' && $nombreArchivo) {
                $obj['filename'] = $nombreArchivo;
            }
            $out[] = ['type' => 'header', 'parameters' => [['type' => $media, $media => $obj]]];
        }

        $b = $this->parte('BODY');
        if ($vars = self::variables($b['text'] ?? '')) {
            $out[] = ['type' => 'body', 'parameters' => array_map(fn ($v) => $param($v, (string) ($valores['body'][$v] ?? '')), $vars)];
        }

        foreach ((array) ($this->parte('BUTTONS')['buttons'] ?? []) as $i => $bt) {
            $tipo = strtoupper((string) ($bt['type'] ?? ''));
            $valor = (string) ($valores['botones'][$i] ?? '');
            if ($tipo === 'URL' && self::variables($bt['url'] ?? '')) {
                $out[] = ['type' => 'button', 'sub_type' => 'url', 'index' => (string) $i, 'parameters' => [['type' => 'text', 'text' => $valor]]];
            } elseif ($tipo === 'COPY_CODE') {
                $out[] = ['type' => 'button', 'sub_type' => 'copy_code', 'index' => (string) $i, 'parameters' => [['type' => 'coupon_code', 'coupon_code' => $valor]]];
            }
        }

        return $out;
    }

    /** ¿Se puede mandar desde aquí? (aprobada y sin partes que no soportamos) */
    public function soportada(): ?string
    {
        if (($this->p['status'] ?? '') !== 'APPROVED') {
            return null;
        }
        foreach ((array) ($this->parte('BUTTONS')['buttons'] ?? []) as $bt) {
            if (in_array(strtoupper((string) ($bt['type'] ?? '')), ['FLOW', 'OTP', 'CATALOG', 'MPM'], true)) {
                return 'Lleva un botón de tipo '.strtoupper($bt['type']).' que no se puede mandar desde Mensajería.';
            }
        }
        if (strtoupper((string) ($this->parte('HEADER')['format'] ?? '')) === 'LOCATION') {
            return 'Lleva una ubicación en el encabezado, que no se puede mandar desde Mensajería.';
        }

        return '';
    }
}
