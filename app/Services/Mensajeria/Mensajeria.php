<?php

namespace App\Services\Mensajeria;

use App\Models\User;
use App\Models\WaConversacion;
use App\Models\WaMensaje;
use App\Services\Promociones\Consentimientos;
use App\Services\WhatsApp\Telefono;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Mensajería de WhatsApp: guarda lo que llega por el webhook, manda texto,
 * archivos, notas de voz y plantillas, y lleva el estado de cada mensaje.
 *
 * Reglas que cuida:
 *  - Un webhook repetido no duplica (wamid único).
 *  - El estado solo avanza; un "entregado" que llega después de "leído" no lo regresa.
 *  - Fuera de la ventana de 24 h solo se puede mandar plantilla.
 *  - Reintentar no duplica: si Meta ya confirmó el envío (por el id que
 *    viaja en biz_opaque_callback_data), no se vuelve a mandar.
 */
class Mensajeria
{
    /** Lo que acepta WhatsApp por tipo (documentación oficial de la Cloud API). */
    public const FORMATOS = [
        'image' => ['mimes' => ['image/jpeg', 'image/png'], 'max' => 5 * 1024 * 1024],
        'video' => ['mimes' => ['video/mp4', 'video/3gpp'], 'max' => 16 * 1024 * 1024],
        'audio' => ['mimes' => ['audio/aac', 'audio/amr', 'audio/mpeg', 'audio/mp4', 'audio/ogg', 'audio/x-m4a', 'audio/opus'], 'max' => 16 * 1024 * 1024],
        'document' => ['mimes' => [
            'application/pdf', 'text/plain',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ], 'max' => 100 * 1024 * 1024],
    ];

    public const ETIQUETAS = [
        'image' => 'Foto', 'video' => 'Video', 'audio' => 'Audio', 'voz' => 'Nota de voz', 'sticker' => 'Sticker',
        'document' => 'Documento', 'location' => 'Ubicación', 'contacts' => 'Contacto', 'interactive' => 'Respuesta',
        'button' => 'Respuesta', 'template' => 'Plantilla', 'order' => 'Pedido', 'system' => 'Aviso del sistema',
        'unsupported' => 'Mensaje no compatible', 'reaction' => 'Reacción',
    ];

    public function __construct(
        private readonly ApiWhatsApp $api,
        private readonly Consentimientos $consentimientos,
    ) {}

    /* ===================================================================
     |  Lo que llega por el webhook
     * =================================================================== */

    /** Un "value" de un cambio del webhook (field = messages). */
    public function recibir(array $valor): void
    {
        $bandeja = (string) ($valor['metadata']['phone_number_id'] ?? $this->api->numeroPrincipal());
        $perfiles = collect((array) ($valor['contacts'] ?? []))
            ->mapWithKeys(fn ($c) => [(string) ($c['wa_id'] ?? '') => (string) ($c['profile']['name'] ?? '')]);

        foreach ((array) ($valor['messages'] ?? []) as $msg) {
            try {
                $this->mensajeEntrante($bandeja, (array) $msg, $perfiles[(string) ($msg['from'] ?? '')] ?? null);
            } catch (\Throwable $e) {
                Log::warning('[Mensajería] No se guardó un mensaje entrante', ['error' => $e->getMessage()]);
            }
        }

        foreach ((array) ($valor['statuses'] ?? []) as $st) {
            try {
                $this->estado((array) $st);
            } catch (\Throwable $e) {
                Log::warning('[Mensajería] No se aplicó un estado', ['error' => $e->getMessage()]);
            }
        }
    }

    private function mensajeEntrante(string $bandeja, array $msg, ?string $perfil): void
    {
        $wamid = (string) ($msg['id'] ?? '');
        $tel = Telefono::e164((string) ($msg['from'] ?? ''));
        $tipo = (string) ($msg['type'] ?? 'unsupported');
        if ($wamid === '' || ! $tel) {
            return;
        }

        if (WaMensaje::where('wamid', $wamid)->exists()) {
            return; // Webhook repetido.
        }

        $conv = $this->conversacion($bandeja, $tel, $perfil);
        $cuando = isset($msg['timestamp']) ? Carbon::createFromTimestamp((int) $msg['timestamp']) : now();

        // Una reacción no es una burbuja: se pega al mensaje que reaccionó.
        if ($tipo === 'reaction') {
            $destino = WaMensaje::where('wamid', (string) ($msg['reaction']['message_id'] ?? ''))->first();
            if ($destino) {
                $datos = $destino->datos ?? [];
                $datos['reaccion'] = (string) ($msg['reaction']['emoji'] ?? '') ?: null;
                $destino->update(['datos' => $datos]);
                $conv->touch();
                $this->cambio();
            }

            return;
        }

        [$cuerpo, $datos, $media] = $this->interpretar($tipo, $msg);

        try {
            $m = $conv->mensajes()->create([
                'wamid' => $wamid,
                'direccion' => WaMensaje::ENTRANTE,
                'tipo' => $tipo,
                'cuerpo' => $cuerpo,
                'datos' => $datos ?: null,
                'responde_a' => $msg['context']['id'] ?? null,
                'estado' => 'recibido',
                'momento' => $cuando,
            ] + $media);
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return; // Llegó dos veces al mismo tiempo.
            }
            throw $e;
        }

        $conv->forceFill([
            'no_leidos' => $conv->no_leidos + 1,
            'ultima_actividad_en' => $cuando,
            'ultimo_entrante_en' => max($cuando, $conv->ultimo_entrante_en ?? $cuando),
            'ultimo_mensaje_id' => $m->id,
            'nombre_perfil' => $perfil ?: $conv->nombre_perfil,
        ])->save();

        if ($m->media_id) {
            // El archivo se baja después de contestarle a Meta (quiere un 200 rápido).
            \Illuminate\Support\defer(fn () => $this->bajarMedia($m->fresh()));
        }

        $this->cambio();
    }

    /** [cuerpo, datos, columnas de media] según el tipo que manda Meta. */
    private function interpretar(string $tipo, array $msg): array
    {
        $o = (array) ($msg[$tipo] ?? []);
        $datos = [];
        $media = [];

        if (! empty($msg['context']['forwarded']) || ! empty($msg['context']['frequently_forwarded'])) {
            $datos['reenviado'] = true;
        }

        switch ($tipo) {
            case 'text':
                return [(string) ($o['body'] ?? ''), $datos, $media];

            case 'image': case 'video': case 'audio': case 'document': case 'sticker':
                $media = [
                    'media_id' => (string) ($o['id'] ?? '') ?: null,
                    'media_mime' => $o['mime_type'] ?? null,
                    'media_nombre' => $o['filename'] ?? null,
                    'media_estado' => ! empty($o['id']) ? 'pendiente' : 'fallo',
                ];
                if ($tipo === 'audio' && ! empty($o['voice'])) {
                    $datos['voz'] = true;
                }
                if ($tipo === 'sticker' && ! empty($o['animated'])) {
                    $datos['animado'] = true;
                }

                return [$o['caption'] ?? null, $datos, $media];

            case 'location':
                $datos['ubicacion'] = [
                    'lat' => $o['latitude'] ?? null, 'lng' => $o['longitude'] ?? null,
                    'nombre' => $o['name'] ?? null, 'direccion' => $o['address'] ?? null, 'url' => $o['url'] ?? null,
                ];

                return [null, $datos, $media];

            case 'contacts':
                $datos['contactos'] = collect((array) ($msg['contacts'] ?? []))->map(fn ($c) => [
                    'nombre' => $c['name']['formatted_name'] ?? trim(($c['name']['first_name'] ?? '').' '.($c['name']['last_name'] ?? '')),
                    'telefonos' => collect((array) ($c['phones'] ?? []))->map(fn ($p) => ['numero' => $p['phone'] ?? null, 'wa_id' => $p['wa_id'] ?? null, 'tipo' => $p['type'] ?? null])->all(),
                    'correos' => collect((array) ($c['emails'] ?? []))->pluck('email')->filter()->values()->all(),
                    'empresa' => $c['org']['company'] ?? null,
                ])->values()->all();

                return [null, $datos, $media];

            case 'interactive':
                $sub = (string) ($o['type'] ?? '');
                $r = (array) ($o[$sub] ?? []);
                $datos['seleccion'] = [
                    'tipo' => $sub === 'list_reply' ? 'lista' : ($sub === 'nfm_reply' ? 'formulario' : 'boton'),
                    'id' => $r['id'] ?? null,
                    'titulo' => $r['title'] ?? ($r['name'] ?? null),
                    'descripcion' => $r['description'] ?? null,
                ];

                return [$r['title'] ?? ($r['body'] ?? null), $datos, $media];

            case 'button':
                $datos['seleccion'] = ['tipo' => 'boton', 'id' => $o['payload'] ?? null, 'titulo' => $o['text'] ?? null];

                return [$o['text'] ?? null, $datos, $media];

            case 'order':
                $datos['pedido'] = [
                    'catalogo' => $o['catalog_id'] ?? null,
                    'productos' => collect((array) ($o['product_items'] ?? []))->map(fn ($p) => [
                        'sku' => $p['product_retailer_id'] ?? null, 'cantidad' => $p['quantity'] ?? null,
                        'precio' => $p['item_price'] ?? null, 'moneda' => $p['currency'] ?? null,
                    ])->all(),
                ];

                return [$o['text'] ?? null, $datos, $media];

            case 'system':
                return [$o['body'] ?? 'Aviso del sistema de WhatsApp', $datos, $media];

            default:
                $err = $msg['errors'][0] ?? null;
                $datos['no_compatible'] = $err['error_data']['details'] ?? $err['title'] ?? 'WhatsApp no entrega el contenido de este tipo de mensaje.';

                return [null, $datos, $media];
        }
    }

    /** Aviso de estado de un mensaje que mandamos. */
    private function estado(array $st): void
    {
        $wamid = (string) ($st['id'] ?? '');
        $estado = (string) ($st['status'] ?? '');
        if ($wamid === '' || $estado === '') {
            return;
        }

        $m = WaMensaje::where('wamid', $wamid)->first();

        // Envío "incierto" (se cayó la conexión): Meta regresa nuestro uuid.
        if (! $m && str_starts_with((string) ($st['biz_opaque_callback_data'] ?? ''), 'msj:')) {
            $m = WaMensaje::where('uuid', substr((string) $st['biz_opaque_callback_data'], 4))->first();
            $m?->forceFill(['wamid' => $wamid]);
        }
        if (! $m || $m->direccion !== WaMensaje::SALIENTE) {
            return;
        }

        $cuando = isset($st['timestamp']) ? Carbon::createFromTimestamp((int) $st['timestamp']) : now();
        $actual = WaMensaje::RANGO[$m->estado] ?? -1;

        $cambios = match ($estado) {
            'sent' => $actual < 1 ? ['estado' => 'enviado', 'error' => null, 'error_codigo' => null] : [],
            'delivered' => ($actual < 2 ? ['estado' => 'entregado', 'error' => null, 'error_codigo' => null] : []) + ['entregado_en' => $m->entregado_en ?? $cuando],
            'read' => ($actual < 3 ? ['estado' => 'leido', 'error' => null, 'error_codigo' => null] : []) + ['leido_en' => $m->leido_en ?? $cuando, 'entregado_en' => $m->entregado_en ?? $cuando],
            // Si ya se entregó o leyó, un "failed" tardío no lo tumba.
            'failed' => $actual < 2 ? [
                'estado' => 'fallo',
                'error_codigo' => (string) ($st['errors'][0]['code'] ?? 'failed'),
                'error' => $this->explicar((string) ($st['errors'][0]['code'] ?? ''), (string) ($st['errors'][0]['error_data']['details'] ?? $st['errors'][0]['title'] ?? 'WhatsApp no pudo entregar el mensaje.')),
            ] : [],
            default => [],
        };

        if ($cambios || $m->isDirty()) {
            $m->forceFill($cambios)->save();
            $m->conversacion?->touch();
            $this->cambio();
        }
    }

    /** Baja (o vuelve a intentar bajar) el archivo de un mensaje entrante. */
    public function bajarMedia(?WaMensaje $m): bool
    {
        if (! $m || ! $m->media_id) {
            return false;
        }

        $r = $this->api->bajar($m->media_id);
        if (! $r['ok']) {
            $m->update(['media_estado' => 'fallo', 'error' => $r['error']]);
            $m->conversacion?->touch();
            $this->cambio();

            return false;
        }

        $mime = $r['mime'] ?: ($m->media_mime ?: 'application/octet-stream');
        $ruta = 'whatsapp/'.now()->format('Y/m').'/'.$m->id.'-'.Str::random(8).'.'.$this->extension($mime, $m->media_nombre);
        Storage::disk('local')->put($ruta, $r['contenido']);

        $m->update([
            'media_path' => $ruta,
            'media_mime' => $mime,
            'media_tamano' => $r['tamano'],
            'media_estado' => 'listo',
            'error' => null,
        ]);
        $m->conversacion?->touch();
        $this->cambio();

        return true;
    }

    /* ===================================================================
     |  Mandar
     * =================================================================== */

    public function enviarTexto(WaConversacion $c, string $texto, User $quien, ?string $uuid = null, ?string $respondeA = null): WaMensaje
    {
        $texto = trim($texto);
        if ($texto === '') {
            throw ValidationException::withMessages(['texto' => 'Escribe un mensaje.']);
        }
        if (mb_strlen($texto) > 4096) {
            throw ValidationException::withMessages(['texto' => 'WhatsApp acepta hasta 4,096 caracteres por mensaje.']);
        }
        $this->exigirVentana($c);

        if ($ya = $this->yaExiste($uuid)) {
            return $ya;
        }

        $m = $this->nuevoSaliente($c, $quien, $uuid, ['tipo' => 'text', 'cuerpo' => $texto, 'responde_a' => $respondeA]);

        return $this->despachar($m, [
            'to' => $c->telefono, 'type' => 'text',
            'text' => ['body' => $texto, 'preview_url' => (bool) preg_match('~https?://~i', $texto)],
        ]);
    }

    /** Foto, video, audio, nota de voz o documento. */
    public function enviarArchivo(WaConversacion $c, UploadedFile $archivo, User $quien, ?string $pie = null, bool $voz = false, ?string $uuid = null, ?string $respondeA = null): WaMensaje
    {
        $this->exigirVentana($c);
        if ($ya = $this->yaExiste($uuid)) {
            return $ya;
        }

        $mime = $this->mimeReal($archivo);
        $tipo = $this->tipoPorMime($mime);
        if (! $tipo) {
            throw ValidationException::withMessages(['archivo' => 'WhatsApp no acepta este formato ('.$mime.'). Fotos JPG o PNG; videos MP4; audios MP3, M4A, AAC, AMR u OGG; documentos PDF, Word, Excel, PowerPoint o TXT.']);
        }
        if ($archivo->getSize() > self::FORMATOS[$tipo]['max']) {
            throw ValidationException::withMessages(['archivo' => 'El archivo pesa '.$this->peso($archivo->getSize()).'; WhatsApp acepta hasta '.$this->peso(self::FORMATOS[$tipo]['max']).' para '.mb_strtolower(self::ETIQUETAS[$tipo]).'.']);
        }
        if ($voz && ! in_array($mime, ['audio/ogg', 'audio/opus'], true)) {
            $voz = false; // Las notas de voz deben ser OGG/Opus; si no, se manda como audio.
        }
        $mime = $mime === 'audio/opus' ? 'audio/ogg' : $mime;

        $nombre = Str::limit(preg_replace('/[^\w\-. ()áéíóúñÁÉÍÓÚÑ]+/u', '_', $archivo->getClientOriginalName()) ?: 'archivo', 180, '');
        $ruta = $archivo->storeAs('whatsapp/'.now()->format('Y/m'), Str::random(12).'.'.$this->extension($mime, $nombre), 'local');

        $m = $this->nuevoSaliente($c, $quien, $uuid, [
            'tipo' => $tipo,
            'cuerpo' => $tipo !== 'audio' && filled($pie) ? Str::limit(trim($pie), 1024, '') : null,
            'responde_a' => $respondeA,
            'datos' => $voz ? ['voz' => true] : null,
            'media_mime' => $mime, 'media_nombre' => $nombre, 'media_tamano' => $archivo->getSize(),
            'media_path' => $ruta, 'media_estado' => 'listo',
        ]);

        return $this->subirYMandar($m);
    }

    /**
     * Manda una plantilla aprobada.
     *
     * @param  array{header?: array<int|string,string>, body?: array<int|string,string>, botones?: array<int,string>}  $valores
     */
    public function enviarPlantilla(WaConversacion $c, string $nombre, string $idioma, array $valores, ?UploadedFile $mediaEncabezado, User $quien, ?string $uuid = null): WaMensaje
    {
        if ($ya = $this->yaExiste($uuid)) {
            return $ya;
        }

        $p = collect($this->api->plantillas() ?? [])->first(fn ($t) => $t['name'] === $nombre && $t['language'] === $idioma);
        if (! $p) {
            throw ValidationException::withMessages(['plantilla' => 'No encontré esa plantilla en la cuenta de WhatsApp.']);
        }
        if (($p['status'] ?? '') !== 'APPROVED') {
            throw ValidationException::withMessages(['plantilla' => 'Esa plantilla no se puede usar: su estado en Meta es '.($p['status'] ?? 'desconocido').'.']);
        }

        $armada = (new PlantillaArmada($p))->armar($valores);
        if ($armada['faltan']) {
            throw ValidationException::withMessages(['plantilla' => 'Falta llenar: '.implode(', ', $armada['faltan']).'.']);
        }

        $datos = ['plantilla' => $armada['vista'] + ['nombre' => $nombre, 'idioma' => $idioma, 'categoria' => $p['category'] ?? null, 'valores' => $valores]];
        $media = [];

        if ($armada['media']) {
            if (! $mediaEncabezado) {
                throw ValidationException::withMessages(['media' => 'Esta plantilla lleva '.mb_strtolower(self::ETIQUETAS[$armada['media']]).' en el encabezado: elige el archivo.']);
            }
            $mime = $this->mimeReal($mediaEncabezado);
            if ($this->tipoPorMime($mime) !== $armada['media']) {
                throw ValidationException::withMessages(['media' => 'El encabezado necesita '.mb_strtolower(self::ETIQUETAS[$armada['media']]).'; el archivo es '.$mime.'.']);
            }
            if ($mediaEncabezado->getSize() > self::FORMATOS[$armada['media']]['max']) {
                throw ValidationException::withMessages(['media' => 'El archivo del encabezado es demasiado pesado para WhatsApp.']);
            }
            $nombreArchivo = Str::limit($mediaEncabezado->getClientOriginalName() ?: 'archivo', 180, '');
            $media = [
                'media_mime' => $mime, 'media_nombre' => $nombreArchivo, 'media_tamano' => $mediaEncabezado->getSize(),
                'media_path' => $mediaEncabezado->storeAs('whatsapp/'.now()->format('Y/m'), Str::random(12).'.'.$this->extension($mime, $nombreArchivo), 'local'),
                'media_estado' => 'listo',
            ];
        }

        $m = $this->nuevoSaliente($c, $quien, $uuid, ['tipo' => 'template', 'cuerpo' => $armada['vista']['cuerpo'] ?? null, 'datos' => $datos] + $media);

        if ($m->media_path) {
            $sub = $this->api->subir($c->phone_number_id, Storage::disk('local')->path($m->media_path), $m->media_mime, $m->media_nombre);
            if (! $sub['ok']) {
                return $this->fallo($m, $sub['codigo'], $sub['error']);
            }
            $m->update(['media_id' => $sub['id']]);
        }

        return $this->despachar($m, [
            'to' => $c->telefono, 'type' => 'template',
            'template' => ['name' => $nombre, 'language' => ['code' => $idioma], 'components' => (new PlantillaArmada($p))->componentes($valores, $m->media_id, $m->media_nombre)],
        ]);
    }

    /** Volver a mandar uno que falló, sin duplicar. */
    public function reintentar(WaMensaje $m): WaMensaje
    {
        if ($m->direccion !== WaMensaje::SALIENTE || $m->estado !== 'fallo') {
            return $m;
        }
        if ($m->wamid) {
            // Meta sí lo recibió (llegó su aviso): no se repite.
            return $m;
        }
        if ($m->error_codigo === 'red' && $m->updated_at->gt(now()->subSeconds(20))) {
            throw ValidationException::withMessages(['mensaje' => 'Todavía estoy esperando la confirmación de WhatsApp de este mensaje. Inténtalo en unos segundos.']);
        }

        $c = $m->conversacion;
        if ($m->tipo !== 'template') {
            $this->exigirVentana($c);
        }

        $m->update(['estado' => 'enviando', 'error' => null, 'error_codigo' => null]);

        if ($m->tipo === 'text') {
            return $this->despachar($m, ['to' => $c->telefono, 'type' => 'text', 'text' => ['body' => (string) $m->cuerpo, 'preview_url' => (bool) preg_match('~https?://~i', (string) $m->cuerpo)]]);
        }

        if ($m->tipo === 'template') {
            $pl = $m->datos['plantilla'] ?? [];
            $p = collect($this->api->plantillas() ?? [])->first(fn ($t) => $t['name'] === ($pl['nombre'] ?? '') && $t['language'] === ($pl['idioma'] ?? ''));
            if (! $p) {
                return $this->fallo($m, 'plantilla', 'La plantilla ya no existe en la cuenta.');
            }

            return $this->despachar($m, [
                'to' => $c->telefono, 'type' => 'template',
                'template' => ['name' => $pl['nombre'], 'language' => ['code' => $pl['idioma']], 'components' => (new PlantillaArmada($p))->componentes($pl['valores'] ?? [], $m->media_id, $m->media_nombre)],
            ]);
        }

        return $this->subirYMandar($m);
    }

    public function marcarLeida(WaConversacion $c): void
    {
        if ($c->no_leidos === 0) {
            return;
        }

        $ultimo = $c->mensajes()->where('direccion', WaMensaje::ENTRANTE)->whereNotNull('wamid')->latest('id')->value('wamid');
        $c->update(['no_leidos' => 0]);
        $this->cambio();

        if ($ultimo && $this->api->configurado()) {
            \Illuminate\Support\defer(fn () => $this->api->marcarLeido($c->phone_number_id, $ultimo));
        }
    }

    /** Abre (o encuentra) la conversación con un número para escribirle con plantilla. */
    public function iniciar(string $telefono, ?string $bandeja = null): WaConversacion
    {
        $tel = Telefono::e164($telefono);
        if (! $tel) {
            throw ValidationException::withMessages(['telefono' => 'Escribe un número válido (10 dígitos o con lada de país).']);
        }

        return $this->conversacion($bandeja ?: $this->api->numeroPrincipal(), $tel, null);
    }

    /* ===================================================================
     |  Para la pantalla
     * =================================================================== */

    public function presentarConversacion(WaConversacion $c): array
    {
        $u = $c->ultimoMensaje;
        $hasta = $c->ventanaHasta();

        return [
            'id' => $c->id,
            'nombre' => $c->nombre(),
            'telefono' => self::telefonoBonito($c->telefono),
            'bandeja' => $c->phone_number_id,
            'cliente_id' => $c->customer_id,
            'no_leidos' => $c->no_leidos,
            'actividad' => ($c->ultima_actividad_en ?? $c->created_at)?->toIso8601String(),
            'ventana_hasta' => $hasta?->toIso8601String(),
            'vista' => $u ? $this->vistaPrevia($u) : null,
            'vista_saliente' => $u?->direccion === WaMensaje::SALIENTE,
            'vista_estado' => $u?->direccion === WaMensaje::SALIENTE ? $u->estado : null,
        ];
    }

    public function presentarMensaje(WaMensaje $m, array $citados = []): array
    {
        $cita = $m->responde_a ? ($citados[$m->responde_a] ?? null) : null;

        return [
            'id' => $m->id,
            'uuid' => $m->uuid,
            'wamid' => $m->wamid,
            'responde_a' => $m->responde_a,
            'conversacion_id' => $m->conversacion_id,
            'dir' => $m->direccion === WaMensaje::ENTRANTE ? 'in' : 'out',
            'tipo' => $m->tipo,
            'cuerpo' => $m->cuerpo,
            'datos' => $m->datos ?? (object) [],
            'cita' => $cita ? ['dir' => $cita->direccion === WaMensaje::ENTRANTE ? 'in' : 'out', 'texto' => $this->vistaPrevia($cita)] : ($m->responde_a ? ['dir' => 'in', 'texto' => 'Mensaje anterior'] : null),
            'media' => $m->media_path || $m->media_id || $m->media_estado ? [
                'url' => $m->media_path ? route('mensajeria.media', $m) : null,
                'mime' => $m->media_mime,
                'nombre' => $m->media_nombre,
                'tamano' => $m->media_tamano,
                'estado' => $m->media_estado,
            ] : null,
            'estado' => $m->estado,
            'error' => $m->error,
            'incierto' => $m->error_codigo === 'red',
            'autor' => $m->autor?->name,
            'momento' => ($m->momento ?? $m->created_at)?->toIso8601String(),
        ];
    }

    /** Mensajes ya presentados, con sus citas resueltas en una sola consulta. */
    public function presentarMensajes($mensajes): array
    {
        $ids = collect($mensajes)->pluck('responde_a')->filter()->unique()->values();
        $citados = $ids->isEmpty() ? [] : WaMensaje::whereIn('wamid', $ids)->get()->keyBy('wamid')->all();

        return collect($mensajes)->map(fn ($m) => $this->presentarMensaje($m, $citados))->values()->all();
    }

    public function vistaPrevia(WaMensaje $m): string
    {
        $etiqueta = ! empty($m->datos['voz']) ? self::ETIQUETAS['voz'] : (self::ETIQUETAS[$m->tipo] ?? null);

        return match (true) {
            $m->tipo === 'text' => Str::limit((string) $m->cuerpo, 90),
            $m->tipo === 'template' => '📋 '.Str::limit((string) ($m->cuerpo ?: ($m->datos['plantilla']['nombre'] ?? 'Plantilla')), 80),
            $m->tipo === 'location' => '📍 '.($m->datos['ubicacion']['nombre'] ?? 'Ubicación'),
            $m->tipo === 'contacts' => '👤 '.($m->datos['contactos'][0]['nombre'] ?? 'Contacto'),
            in_array($m->tipo, ['interactive', 'button'], true) => '↩ '.Str::limit((string) $m->cuerpo, 80),
            $m->tipo === 'document' => '📄 '.($m->media_nombre ?: ($m->cuerpo ?: 'Documento')),
            in_array($m->tipo, ['image', 'video', 'audio', 'sticker'], true) => ['image' => '📷', 'video' => '🎬', 'audio' => '🎤', 'sticker' => '✨'][$m->tipo].' '.($m->cuerpo ? Str::limit($m->cuerpo, 70) : $etiqueta),
            default => $etiqueta ?? 'Mensaje',
        };
    }

    /** +52 55 3668 8172 / +52 220 538 1046 (México) o +<dígitos> para otros países. */
    public static function telefonoBonito(string $tel): string
    {
        if (preg_match('/^52(\d{10})$/', $tel, $m)) {
            $d = $m[1];
            // CDMX, Guadalajara y Monterrey tienen lada de 2 dígitos; el resto, de 3.
            return in_array(substr($d, 0, 2), ['55', '56', '33', '81'], true)
                ? '+52 '.substr($d, 0, 2).' '.substr($d, 2, 4).' '.substr($d, 6)
                : '+52 '.substr($d, 0, 3).' '.substr($d, 3, 3).' '.substr($d, 6);
        }

        return '+'.$tel;
    }

    /** Sube cada vez que algo cambia en Mensajería (lo consulta la pantalla). */
    public static function revision(): string
    {
        return (string) Cache::store('file')->get('mensajeria:rev', '0');
    }

    public function cambio(): void
    {
        Cache::store('file')->forever('mensajeria:rev', (string) hrtime(true));
    }

    /* ===================================================================
     |  Internos
     * =================================================================== */

    private function conversacion(string $bandeja, string $tel, ?string $perfil): WaConversacion
    {
        $c = WaConversacion::firstOrCreate(
            ['phone_number_id' => $bandeja, 'telefono' => $tel],
            ['nombre_perfil' => $perfil ?: null, 'ultima_actividad_en' => now()]
        );

        if (! $c->customer_id) {
            $cliente = $this->consentimientos->clientesConTelefono($tel)->first();
            if ($cliente) {
                $c->update(['customer_id' => $cliente->id]);
            }
        }

        return $c;
    }

    private function exigirVentana(WaConversacion $c): void
    {
        if (! $c->ventanaHasta()) {
            throw ValidationException::withMessages(['ventana' => 'Pasaron más de 24 horas desde el último mensaje del cliente: para escribirle hay que mandar una plantilla.']);
        }
    }

    private function yaExiste(?string $uuid): ?WaMensaje
    {
        return $uuid ? WaMensaje::where('uuid', $uuid)->first() : null;
    }

    private function nuevoSaliente(WaConversacion $c, User $quien, ?string $uuid, array $campos): WaMensaje
    {
        $m = $c->mensajes()->create($campos + [
            'uuid' => $uuid && Str::isUuid($uuid) ? $uuid : (string) Str::uuid(),
            'direccion' => WaMensaje::SALIENTE,
            'estado' => 'enviando',
            'enviado_por' => $quien->id,
            'momento' => now(),
        ]);

        $c->forceFill(['ultima_actividad_en' => now(), 'ultimo_mensaje_id' => $m->id])->save();
        $this->cambio();

        return $m;
    }

    private function subirYMandar(WaMensaje $m): WaMensaje
    {
        $c = $m->conversacion;

        if (! $m->media_id) {
            $sub = $this->api->subir($c->phone_number_id, Storage::disk('local')->path($m->media_path), $m->media_mime, $m->media_nombre ?: 'archivo');
            if (! $sub['ok']) {
                return $this->fallo($m, $sub['codigo'], $sub['error']);
            }
            $m->update(['media_id' => $sub['id']]);
        }

        $obj = ['id' => $m->media_id];
        if ($m->cuerpo && in_array($m->tipo, ['image', 'video', 'document'], true)) {
            $obj['caption'] = $m->cuerpo;
        }
        if ($m->tipo === 'document') {
            $obj['filename'] = $m->media_nombre;
        }
        if ($m->tipo === 'audio' && ! empty($m->datos['voz'])) {
            $obj['voice'] = true;
        }

        return $this->despachar($m, ['to' => $c->telefono, 'type' => $m->tipo, $m->tipo => $obj]);
    }

    private function despachar(WaMensaje $m, array $payload): WaMensaje
    {
        if ($m->responde_a) {
            $payload['context'] = ['message_id' => $m->responde_a];
        }
        $payload['biz_opaque_callback_data'] = 'msj:'.$m->uuid;

        $r = $this->api->enviar($m->conversacion->phone_number_id, $payload);

        if (! $r['ok']) {
            return $this->fallo($m, $r['incierto'] ? 'red' : $r['codigo'], $r['error']);
        }

        // Puede que el aviso de Meta haya llegado antes que esta respuesta.
        $m->refresh();
        $m->forceFill([
            'wamid' => $m->wamid ?: $r['wamid'],
            'estado' => (WaMensaje::RANGO[$m->estado] ?? 0) >= 1 ? $m->estado : 'enviado',
            'error' => null, 'error_codigo' => null,
        ])->save();
        $m->conversacion->touch();
        $this->cambio();

        return $m;
    }

    private function fallo(WaMensaje $m, ?string $codigo, ?string $error): WaMensaje
    {
        $m->update(['estado' => 'fallo', 'error_codigo' => $codigo, 'error' => $this->explicar((string) $codigo, (string) $error)]);
        $m->conversacion->touch();
        $this->cambio();

        return $m;
    }

    private function explicar(string $codigo, string $error): string
    {
        return match ($codigo) {
            '131047' => 'Pasaron más de 24 horas desde el último mensaje del cliente: manda una plantilla.',
            '131026' => 'No se pudo entregar: el número no tiene WhatsApp o no puede recibir este mensaje.',
            '131051' => 'WhatsApp no admite este tipo de mensaje.',
            '131053' => 'WhatsApp no pudo procesar el archivo (formato o tamaño no válido).',
            '132000', '132001', '132012' => 'La plantilla no coincide con lo que está aprobado (variables o idioma).',
            '130429', '131056' => 'WhatsApp está limitando los envíos. Reintenta en un momento.',
            '131049' => 'Meta no entregó este mensaje de marketing para cuidar la experiencia del cliente.',
            default => Str::limit($error !== '' ? $error : 'WhatsApp no pudo mandar el mensaje.', 480),
        };
    }

    private function mimeReal(UploadedFile $f): string
    {
        $mime = (string) ($f->getMimeType() ?: $f->getClientMimeType());
        // Algunos navegadores marcan la nota de voz como video/ogg o application/ogg.
        if (in_array($mime, ['video/ogg', 'application/ogg'], true) && str_ends_with(strtolower($f->getClientOriginalName()), '.ogg')) {
            return 'audio/ogg';
        }

        return $mime === 'audio/x-m4a' ? 'audio/mp4' : $mime;
    }

    private function tipoPorMime(string $mime): ?string
    {
        foreach (self::FORMATOS as $tipo => $f) {
            if (in_array($mime, $f['mimes'], true)) {
                return $tipo;
            }
        }

        return null;
    }

    private function extension(string $mime, ?string $nombre): string
    {
        $ext = strtolower(pathinfo((string) $nombre, PATHINFO_EXTENSION));
        if ($ext !== '' && strlen($ext) <= 5 && ctype_alnum($ext)) {
            return $ext;
        }

        return [
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'video/mp4' => 'mp4', 'video/3gpp' => '3gp',
            'audio/ogg' => 'ogg', 'audio/mpeg' => 'mp3', 'audio/mp4' => 'm4a', 'audio/aac' => 'aac', 'audio/amr' => 'amr',
            'application/pdf' => 'pdf',
        ][explode(';', $mime)[0]] ?? 'bin';
    }

    private function peso(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1).' MB' : max(1, round($bytes / 1024)).' KB';
    }
}
