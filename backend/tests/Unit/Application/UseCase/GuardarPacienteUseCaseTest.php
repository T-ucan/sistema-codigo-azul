<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Unit\Application\UseCase;

use CodigoAzul\Application\UseCase\Paciente\GuardarPacienteUseCase;
use CodigoAzul\Application\Validation\PacienteValidator;
use CodigoAzul\Domain\Exception\ValidationException;
use CodigoAzul\Tests\Fakes\FakePacienteRepository;
use PHPUnit\Framework\TestCase;

final class GuardarPacienteUseCaseTest extends TestCase
{
    public function test_crea_un_paciente_valido(): void
    {
        $repositorio = new FakePacienteRepository();
        $guardar = new GuardarPacienteUseCase($repositorio, new PacienteValidator($repositorio));

        $paciente = $guardar('Ana Fernández', '28456789', '1968-09-03', 'Diabetes tipo II.', '2', null);

        self::assertNotNull($paciente->id());
        self::assertSame('28456789', $paciente->dni());
    }

    public function test_rechaza_dni_duplicado_de_extremo_a_extremo(): void
    {
        $repositorio = new FakePacienteRepository();
        $guardar = new GuardarPacienteUseCase($repositorio, new PacienteValidator($repositorio));
        $guardar('Paciente Uno', '30123456', '1990-01-01', '', '1', null);

        $this->expectException(ValidationException::class);

        $guardar('Paciente Dos', '30123456', '1991-01-01', '', '1', null);
    }
}
