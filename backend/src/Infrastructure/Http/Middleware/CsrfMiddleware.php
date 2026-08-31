<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Http\Middleware;

use CodigoAzul\Infrastructure\Http\JsonResponse;
use CodigoAzul\Infrastructure\Http\Request;

/**
 * Patron de token sincronizado: toda peticion que muta datos (POST/PUT/
 * DELETE) debe repetir en la cabecera X-CSRF-Token el valor que la sesion
 * le entrego en la respuesta anterior. Protege altas/bajas de usuarios,
 * pacientes y fichas medicas contra Cross-Site Request Forgery.
 */
final class CsrfMiddleware implements Middleware
{
    private const METODOS_PROTEGIDOS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function manejar(Request $solicitud, \Closure $siguiente): JsonResponse
    {
        $requiereToken = in_array($solicitud->metodo, self::METODOS_PROTEGIDOS, true);

        if ($requiereToken && !$this->tokenCoincide($solicitud)) {
            return JsonResponse::error('Token CSRF inválido o ausente.', 403);
        }

        return $siguiente($solicitud);
    }

    private function tokenCoincide(Request $solicitud): bool
    {
        $tokenRecibido = $solicitud->cabecera('X-CSRF-Token') ?? '';
        $tokenSesion = $_SESSION['csrfToken'] ?? '';

        return $tokenRecibido !== '' && hash_equals($tokenSesion, $tokenRecibido);
    }
}
