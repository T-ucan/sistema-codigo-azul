<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Unit\Application\UseCase;

use CodigoAzul\Application\UseCase\CodigoAzul\RegistrarCodigoAzulUseCase;
use CodigoAzul\Application\Validation\CodigoAzulValidator;
use CodigoAzul\Domain\Exception\ValidationException;
use CodigoAzul\Tests\Fakes\FakeCodigoAzulRepository;
use PHPUnit\Framework\TestCase;

final class RegistrarCodigoAzulUseCaseTest extends TestCase
{
    public function test_registra_una_ficha_valida_y_calcula_tiempo_de_respuesta(): void
    {
        $repositorio = new FakeCodigoAzulRepository();
        $registrar = new RegistrarCodigoAzulUseCase($repositorio, new CodigoAzulValidator());

        $ficha = $registrar('2026-01-01T10:00', '5', '2026-01-01T10:03', 'Equipo A', 'RCP', 'RCE', '', null, 1);

        self::assertSame(3, $ficha->tiempoRespuesta());
    }

    public function test_hora_de_llegada_anterior_al_evento_es_invalida(): void
    {
        $repositorio = new FakeCodigoAzulRepository();
        $registrar = new RegistrarCodigoAzulUseCase($repositorio, new CodigoAzulValidator());

        $this->expectException(ValidationException::class);

        $registrar('2026-01-01T10:05', '5', '2026-01-01T10:00', 'Equipo A', 'RCP', 'RCE', '', null, 1);
    }

    public function test_sin_paciente_es_invalido(): void
    {
        $repositorio = new FakeCodigoAzulRepository();
        $registrar = new RegistrarCodigoAzulUseCase($repositorio, new CodigoAzulValidator());

        $this->expectException(ValidationException::class);

        $registrar('2026-01-01T10:00', null, '2026-01-01T10:03', 'Equipo A', 'RCP', 'RCE', '', null, 1);
    }
}
