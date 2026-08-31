<?php

declare(strict_types=1);

namespace CodigoAzul\Infrastructure\Http;

/**
 * Unico lugar que serializa una respuesta y la envia al cliente. Los
 * controladores nunca hacen echo/json_encode/http_response_code por su
 * cuenta: todos devuelven un JsonResponse.
 */
final class JsonResponse
{
    /** @param array<string, mixed> $cuerpo */
    private function __construct(
        private readonly int $codigoEstado,
        private readonly array $cuerpo,
    ) {
    }

    /** @param array<string, mixed> $datos */
    public static function exito(array $datos = [], int $codigoEstado = 200): self
    {
        return new self($codigoEstado, ['ok' => true, ...$datos]);
    }

    /** @param string[] $errores */
    public static function error(string $mensaje, int $codigoEstado, array $errores = []): self
    {
        $detalle = $errores === [] ? [] : ['errores' => $errores];

        return new self($codigoEstado, ['ok' => false, 'mensaje' => $mensaje, ...$detalle]);
    }

    public function conDato(string $clave, mixed $valor): self
    {
        return new self($this->codigoEstado, [...$this->cuerpo, $clave => $valor]);
    }

    public function enviar(): void
    {
        http_response_code($this->codigoEstado);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->cuerpo, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
