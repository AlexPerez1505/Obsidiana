<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class LoginLogger
{
    /**
     * Registra un inicio de sesión: IP, navegador, plataforma y ubicación.
     */
    public function record(User $user, Request $request): void
    {
        $agent = (string) $request->userAgent();
        $ip = (string) $request->ip();

        $user->loginLogs()->create([
            'ip_address' => $ip,
            'location'   => $this->locationFromIp($ip),
            'browser'    => $this->browserFromAgent($agent),
            'platform'   => $this->platformFromAgent($agent),
            'user_agent' => $agent,
            'logged_at'  => Carbon::now(),
        ]);
    }

    /**
     * Detecta el navegador a partir del User-Agent (sin librerías externas).
     */
    protected function browserFromAgent(string $agent): string
    {
        return match (true) {
            str_contains($agent, 'Edg')                                  => 'Edge',
            str_contains($agent, 'OPR') || str_contains($agent, 'Opera') => 'Opera',
            str_contains($agent, 'Chrome')                               => 'Chrome',
            str_contains($agent, 'Firefox')                              => 'Firefox',
            str_contains($agent, 'Safari')                               => 'Safari',
            default                                                      => 'Desconocido',
        };
    }

    /**
     * Detecta el sistema operativo a partir del User-Agent.
     */
    protected function platformFromAgent(string $agent): string
    {
        return match (true) {
            str_contains($agent, 'Windows')            => 'Windows',
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Android')            => 'Android',
            str_contains($agent, 'Mac OS') || str_contains($agent, 'Macintosh') => 'macOS',
            str_contains($agent, 'Linux')              => 'Linux',
            default                                     => 'Desconocido',
        };
    }

    /**
     * Ubicación aproximada por IP. Para IPs locales devuelve "Red local".
     * Usa el servicio gratuito ip-api.com (sin API key). Si falla, devuelve null.
     */
    protected function locationFromIp(string $ip): ?string
    {
        if ($ip === '' || $ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return 'Red local';
        }

        try {
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,country,regionName,city',
            ]);

            if ($response->ok() && $response->json('status') === 'success') {
                return collect([
                    $response->json('city'),
                    $response->json('regionName'),
                    $response->json('country'),
                ])->filter()->implode(', ');
            }
        } catch (\Throwable $e) {
            // Si no hay internet o el servicio falla, no bloqueamos el login.
        }

        return null;
    }
}
