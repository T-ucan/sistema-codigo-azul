<?php

declare(strict_types=1);

namespace CodigoAzul\Application\Validation;

/** Utilidades puras de texto compartidas por los validadores. */
final class Texto
{
    private function __construct()
    {
    }

    public static function normalizar(?string $texto): string
    {
        return mb_strtolower(trim($texto ?? ''));
    }

    public static function tieneLargoMinimo(?string $texto, int $minimo): bool
    {
        return mb_strlen(trim($texto ?? '')) >= $minimo;
    }
}
