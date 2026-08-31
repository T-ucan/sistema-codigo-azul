<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Unit\Application\Validation;

use CodigoAzul\Application\Validation\RuleValidator;
use PHPUnit\Framework\TestCase;

final class RuleValidatorTest extends TestCase
{
    public function test_sin_reglas_no_hay_errores(): void
    {
        self::assertSame([], RuleValidator::evaluar([]));
    }

    public function test_solo_devuelve_los_mensajes_de_las_reglas_invalidas(): void
    {
        $errores = RuleValidator::evaluar([
            ['valido' => true, 'mensaje' => 'no deberia aparecer'],
            ['valido' => false, 'mensaje' => 'primer error'],
            ['valido' => false, 'mensaje' => 'segundo error'],
        ]);

        self::assertSame(['primer error', 'segundo error'], $errores);
    }
}
