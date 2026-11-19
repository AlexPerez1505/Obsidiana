<?php

namespace App\Services\Almacen;

use App\Models\OrdenSalida;
use App\Models\OrdenSalidaEvidencia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * El expediente de salida en PDF: quién hizo cada etapa, su checklist, lo
 * que agregó o con qué piezas salió, la evidencia (en miniatura) y las
 * firmas, hasta la entrega.
 */
class ExpedienteDeSalida
{
    /** Fotos por etapa que caben en el PDF; las demás se cuentan. */
    public const FOTOS_POR_ETAPA = 9;

    public function __construct(private readonly EtapasDeSalida $etapas) {}

    /** @return \Barryvdh\DomPDF\PDF */
    public function pdf(OrdenSalida $orden)
    {
        $orden->load(['venta.customer', 'venta.seller', 'items', 'evidencias.subidoPor', 'preparadaPor', 'entregadaPor']);
        $etapas = $this->etapas->asegurar($orden);
        $disco = Storage::disk(config('filesystems.fotos_disk', 'public'));

        $evidencia = [];
        foreach ($etapas as $e) {
            $archivos = $orden->evidencias->where('etapa', $e->etapa)->values();
            $fotos = [];
            $otros = [];
            foreach ($archivos as $a) {
                $mini = count($fotos) < self::FOTOS_POR_ETAPA && $a->tipo === OrdenSalidaEvidencia::FOTO ? $this->miniatura($a->path) : null;
                if ($mini) {
                    $fotos[] = ['src' => $mini, 'quien' => $a->subidoPor?->name, 'cuando' => $a->created_at?->format('d/m/Y H:i')];
                } else {
                    $otros[] = ($a->tipo === OrdenSalidaEvidencia::VIDEO ? 'Video' : 'Foto').': '.($a->nombre_original ?: basename($a->path));
                }
            }
            $firmaRecibe = ! empty($e->datos['firma_recibe_path']) && $disco->exists($e->datos['firma_recibe_path'])
                ? 'data:image/png;base64,'.base64_encode($disco->get($e->datos['firma_recibe_path']))
                : null;
            $evidencia[$e->etapa] = ['fotos' => $fotos, 'otros' => $otros, 'total' => $archivos->count(), 'firma' => $e->firmaDataUri(), 'firma_recibe' => $firmaRecibe];
        }

        // Evidencia sin etapa (la que se subía antes en la orden).
        $sueltas = $orden->evidencias->whereNull('etapa')->values();
        $fotosSueltas = $sueltas->where('tipo', OrdenSalidaEvidencia::FOTO)->take(self::FOTOS_POR_ETAPA)
            ->map(fn ($a) => $this->miniatura($a->path))->filter()->values()->all();

        return Pdf::loadView('structure.gestion_Inventario.ordenes_salida.expediente_pdf', [
            'orden' => $orden,
            'etapas' => $etapas,
            'evidencia' => $evidencia,
            'fotosSueltas' => $fotosSueltas,
            'totalSueltas' => $sueltas->count(),
        ])->setPaper('letter');
    }

    /**
     * Una foto chica (máx. 520 px, JPG) embebida: así el PDF no pesa lo que
     * pesan las fotos originales. Si el servidor no puede abrirla (RAW,
     * TIFF…), regresa null y se lista por nombre.
     */
    private function miniatura(string $path): ?string
    {
        $disco = Storage::disk(config('filesystems.fotos_disk', 'public'));

        try {
            if (! function_exists('imagecreatefromstring') || ! $disco->exists($path) || $disco->size($path) > 40 * 1024 * 1024) {
                return null;
            }
            $contenido = $disco->get($path);
            $img = @imagecreatefromstring($contenido);
            if (! $img) {
                return null;
            }

            // Las fotos del celular vienen giradas: se enderezan con su EXIF.
            if (function_exists('exif_read_data') && str_starts_with($contenido, "\xFF\xD8")) {
                $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($contenido));
                $giro = match ((int) ($exif['Orientation'] ?? 1)) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
                if ($giro && ($girada = imagerotate($img, $giro, 0))) {
                    imagedestroy($img);
                    $img = $girada;
                }
            }

            $w = imagesx($img);
            $h = imagesy($img);
            $escala = min(1, 520 / max($w, $h));
            $nueva = imagecreatetruecolor(max(1, (int) ($w * $escala)), max(1, (int) ($h * $escala)));
            imagefill($nueva, 0, 0, imagecolorallocate($nueva, 255, 255, 255));
            imagecopyresampled($nueva, $img, 0, 0, 0, 0, imagesx($nueva), imagesy($nueva), $w, $h);
            ob_start();
            imagejpeg($nueva, null, 72);
            $jpg = ob_get_clean();
            imagedestroy($img);
            imagedestroy($nueva);

            return 'data:image/jpeg;base64,'.base64_encode($jpg);
        } catch (Throwable) {
            return null;
        }
    }
}
