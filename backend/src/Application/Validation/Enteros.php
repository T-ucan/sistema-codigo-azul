<?php

declare(strict_types=1);

namespace CodigoAzul\Application\Validation;

/** Conversion segura de campos de formulario (string|null) a int|null. */
final class Enteros
{
    private function __construct()
    {
    }

    public static function opcional(?string $crudo): ?int
    {
        return ($crudo === null || $crudo === '') ? null : (int) $crudo;
    }
}
