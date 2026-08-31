<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Http\Middleware;

use CodigoAzul\Infrastructure\Http\JsonResponse;
use CodigoAzul\Infrastructure\Http\Request;

/**
 * Capa del pipeline de seguridad del Front Controller. Cada middleware
 * decide si continua la cadena (llamando a $siguiente) o corta el flujo
 * devolviendo una respuesta propia (por ejemplo, un 403 por CSRF invalido).
 */
interface Middleware
{
    public function manejar(Request $solicitud, \Closure $siguiente): JsonResponse;
}
