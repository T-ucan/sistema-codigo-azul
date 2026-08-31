<?php

declare(strict_types=1);

namespace CodigoAzul\Application\Validation;

/**
 * Motor de validacion generico: recibe una lista de reglas (valido/mensaje)
 * y devuelve los mensajes de las que fallaron. Mismo enfoque que el
 * front-end (Validador._errores): evita encadenar ifs por cada campo.
 */
final class RuleValidator
{
    /**
     * @param array<int, array{valido: bool, mensaje: string}> $reglas
     * @return string[]
     */
    public static function evaluar(array $reglas): array
    {
        $errores = [];
        foreach ($reglas as $regla) {
            if (!$regla['valido']) {
                $errores[] = $regla['mensaje'];
            }
        }

        return $errores;
    }
}
