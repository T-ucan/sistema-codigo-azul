<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Http\Middleware;

use CodigoAzul\Infrastructure\Http\JsonResponse;
use CodigoAzul\Infrastructure\Http\Request;

/**
 * Cabeceras de seguridad para TODA respuesta del backend: es una API JSON
 * pura (sin vistas propias), asi que se bloquea explicitamente todo lo que
 * no sea el propio origen (nada de scripts, framing ni MIME sniffing).
 */
final class SecurityHeadersMiddleware implements Middleware
{
    public function manejar(Request $solicitud, \Closure $siguiente): JsonResponse
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        if ($this->esHttps()) {
            header('Strict-Transport-Security: max-age=63072000; includeSubDomains');
        }

        return $siguiente($solicitud);
    }

    private function esHttps(): bool
    {
        return ($_SERVER['HTTPS'] ?? '') !== '';
    }
}
