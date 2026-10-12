<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Previsualización híbrida del enlace de entrega de una pieza:
 *  - YouTube / Shorts / youtu.be  -> embed de video
 *  - Canva                         -> embed de diseño
 *  - Vimeo                         -> embed de video
 *  - Google Drive (file/d/...)     -> preview de Drive
 *  - Imagen directa (.jpg/.png/...) -> <img>
 *  - Cualquier otro link           -> tarjeta OpenGraph (og:image/title/description)
 *
 * El resultado se cachea 7 días por URL para no volver a pedir la página
 * cada vez que se abre el modal (o la app) de la tarea.
 */
class PreviewDeEnlaces
{
    /**
     * @return array{type: string, url: string, embed_url?: string, embed_html?: string, title?: string, description?: string|null, image?: string|null}
     */
    public function obtener(string $url): array
    {
        return Cache::remember('link_preview:'.md5($url), now()->addDays(7), fn () => $this->resolver($url));
    }

    /**
     * Resuelve el tipo de preview y sus datos para una URL dada.
     *
     * @return array{type: string, url: string, embed_url?: string, embed_html?: string, title?: string, description?: string|null, image?: string|null}
     */
    private function resolver(string $url): array
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);
        $query = (string) parse_url($url, PHP_URL_QUERY);
        parse_str($query, $queryParams);

        // 1) YouTube: youtube.com/watch?v=ID, youtu.be/ID, youtube.com/shorts/ID, youtube.com/embed/ID
        if ($host === 'www.youtube.com' || $host === 'youtube.com' || $host === 'm.youtube.com' || $host === 'youtu.be') {
            $videoId = null;
            if ($host === 'youtu.be') {
                $videoId = ltrim($path, '/');
            } elseif (isset($queryParams['v'])) {
                $videoId = $queryParams['v'];
            } elseif (preg_match('#/(?:shorts|embed)/([A-Za-z0-9_-]{6,})#', $path, $m)) {
                $videoId = $m[1];
            }
            if ($videoId) {
                return [
                    'type' => 'youtube',
                    'url' => $url,
                    'embed_url' => 'https://www.youtube.com/embed/'.$videoId,
                    'title' => 'Video de YouTube',
                ];
            }
        }

        // 2) Canva: canva.com/design/.../edit o /view
        if (str_contains($host, 'canva.com')) {
            // Canva soporta oEmbed: https://www.canva.com/_oembed?url=...
            try {
                $oembed = Http::timeout(8)->get('https://www.canva.com/_oembed', ['url' => $url]);
                if ($oembed->ok()) {
                    $data = $oembed->json();
                    $html = $data['html'] ?? null;
                    if ($html) {
                        return [
                            'type' => 'canva',
                            'url' => $url,
                            'embed_html' => $html,
                            'title' => $data['title'] ?? 'Diseno de Canva',
                            'image' => $data['thumbnail_url'] ?? null,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // fallback abajo
            }
            // Fallback: usar el link de embed de Canva
            if (preg_match('#https://([a-z0-9.-]*canva\.com)/design/([A-Za-z0-9_-]+)/#', $url, $m)) {
                $embed = 'https://'.$m[1].'/design/'.$m[2].'/watch?embed';

                return [
                    'type' => 'canva',
                    'url' => $url,
                    'embed_url' => $embed,
                    'title' => 'Diseno de Canva',
                ];
            }
        }

        // 3) Vimeo: vimeo.com/ID
        if (($host === 'vimeo.com' || $host === 'www.vimeo.com') && preg_match('#/(\d{5,})#', $path, $m)) {
            return [
                'type' => 'vimeo',
                'url' => $url,
                'embed_url' => 'https://player.vimeo.com/video/'.$m[1],
                'title' => 'Video de Vimeo',
            ];
        }

        // 4) Google Drive: drive.google.com/file/d/ID/view o /preview
        if (str_contains($host, 'drive.google.com') && preg_match('#/file/d/([A-Za-z0-9_-]{10,})#', $path, $m)) {
            return [
                'type' => 'drive',
                'url' => $url,
                'embed_url' => 'https://drive.google.com/file/d/'.$m[1].'/preview',
                'title' => 'Archivo de Google Drive',
            ];
        }

        // 5) Imagen directa por extension
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'], true)) {
            return [
                'type' => 'image',
                'url' => $url,
                'image' => $url,
            ];
        }

        // 6) OpenGraph: fetchear HTML y parsear meta tags
        return $this->resolverOpenGraph($url);
    }

    /**
     * Hace fetch del HTML y extrae metadatos OpenGraph / Twitter / <title>.
     *
     * @return array{type: string, url: string, title?: string, description?: string|null, image?: string|null}
     */
    private function resolverOpenGraph(string $url): array
    {
        $preview = ['type' => 'og', 'url' => $url];

        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; ObsidianaBot/1.0; +link-preview)'])
                ->get($url);
        } catch (\Throwable $e) {
            $preview['type'] = 'link';
            $preview['title'] = parse_url($url, PHP_URL_HOST) ?: $url;

            return $preview;
        }

        if (! $response->ok()) {
            $preview['type'] = 'link';
            $preview['title'] = parse_url($url, PHP_URL_HOST) ?: $url;

            return $preview;
        }

        $html = $response->body();
        if (empty($html)) {
            $preview['type'] = 'link';
            $preview['title'] = parse_url($url, PHP_URL_HOST) ?: $url;

            return $preview;
        }

        // Extraer <meta property="og:..." content="...">
        $metas = [];
        if (preg_match_all('#<meta[^>]+(?:property|name)=["\']([^"\']+)["\'][^>]+content=["\']([^"\']*)["\']#i', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $key = strtolower($m[1]);
                if (! isset($metas[$key])) {
                    $metas[$key] = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5);
                }
            }
        }

        $preview['title'] = $metas['og:title']
            ?? $metas['twitter:title']
            ?? $this->extraerTitleTag($html)
            ?? parse_url($url, PHP_URL_HOST)
            ?: $url;

        $preview['description'] = $metas['og:description']
            ?? $metas['twitter:description']
            ?? $metas['description']
            ?? null;

        $preview['image'] = $metas['og:image']
            ?? $metas['og:image:secure_url']
            ?? $metas['twitter:image']
            ?? null;

        // Si no hay imagen ni titulo util, fallback a link simple
        if (empty($preview['image']) && ($preview['title'] === $url || empty($preview['title']))) {
            $preview['type'] = 'link';
        }

        return $preview;
    }

    private function extraerTitleTag(string $html): ?string
    {
        if (preg_match('#<title[^>]*>([^<]+)</title>#i', $html, $m)) {
            $title = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5));

            return $title !== '' ? $title : null;
        }

        return null;
    }
}
