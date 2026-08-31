<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Http\ErrorHandling;

use CodigoAzul\Domain\Exception\AuthenticationException;
use CodigoAzul\Domain\Exception\ConflictException;
use CodigoAzul\Domain\Exception\ForbiddenException;
use CodigoAzul\Domain\Exception\NotFoundException;
use CodigoAzul\Domain\Exception\ValidationException;
use CodigoAzul\Infrastructure\Http\JsonResponse;

/**
 * Unico lugar que traduce una excepcion a una respuesta HTTP. Nunca deja
 * escapar un stack trace, un mensaje de PDO ni el nombre de una tabla/
 * columna hacia el cliente: eso es exactamente el tipo de fuga de datos
 * sensibles que el Front Controller existe para evitar. Los errores
 * inesperados se registran en el log del servidor y se devuelven
 * genericos.
 *
 * Tabla de busqueda en vez de switch/if-elseif encadenado por tipo de
 * excepcion (mismo criterio que el resto del proyecto).
 */
final class ManejadorErrores
{
    private const MAPA_EXCEPCIONES = [
        AuthenticationException::class => 401,
        ForbiddenException::class => 403,
        NotFoundException::class => 404,
        ConflictException::class => 409,
    ];

    public function __construct(private readonly bool $modoDesarrollo)
    {
    }

    public function manejar(\Throwable $error): JsonResponse
    {
        if ($error instanceof ValidationException) {
            return JsonResponse::error('Datos inválidos.', 422, $error->errores());
        }

        $codigoConocido = $this->buscarCodigoConocido($error);

        return $codigoConocido !== null
            ? JsonResponse::error($error->getMessage(), $codigoConocido)
            : $this->errorInesperado($error);
    }

    private function buscarCodigoConocido(\Throwable $error): ?int
    {
        foreach (self::MAPA_EXCEPCIONES as $clase => $codigo) {
            if ($error instanceof $clase) {
                return $codigo;
            }
        }

        return null;
    }

    private function errorInesperado(\Throwable $error): JsonResponse
    {
        error_log(sprintf(
            '[codigo-azul] %s: %s en %s:%d',
            $error::class,
            $error->getMessage(),
            $error->getFile(),
            $error->getLine(),
        ));

        $mensaje = $this->modoDesarrollo ? $error->getMessage() : 'Ocurrió un error interno. Intente nuevamente.';

        return JsonResponse::error($mensaje, 500);
    }
}
