<?php

declare(strict_types=1);

namespace CodigoAzul\Domain\Exception;

/**
 * Agrupa una lista de errores de validacion (mismo enfoque que el
 * front-end: lista de reglas, sin ifs anidados por campo).
 */
final class ValidationException extends \RuntimeException
{
    /** @param string[] $errores */
    public function __construct(private readonly array $errores)
    {
        parent::__construct(implode(' ', $errores));
    }

    /** @return string[] */
    public function errores(): array
    {
        return $this->errores;
    }
}
