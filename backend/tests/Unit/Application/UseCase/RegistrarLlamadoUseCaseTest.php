<?php

declare(strict_types=1);

namespace CodigoAzul\Tests\Unit\Application\UseCase;

use CodigoAzul\Application\UseCase\Llamado\RegistrarLlamadoUseCase;
use CodigoAzul\Application\Validation\LlamadoValidator;
use CodigoAzul\Domain\Exception\ValidationException;
use CodigoAzul\Domain\Model\EstadoLlamado;
use CodigoAzul\Tests\Fakes\FakeLlamadoRepository;
use PHPUnit\Framework\TestCase;

final class RegistrarLlamadoUseCaseTest extends TestCase
{
    public function test_registra_un_llamado_como_pendiente_sin_tiempo_de_respuesta(): void
    {
        $repositorio = new FakeLlamadoRepository();
        $registrar = new RegistrarLlamadoUseCase($repositorio, new LlamadoValidator());

        $llamado = $registrar('2026-01-01T10:00', 'Normal', 'Cama', null, 'Enf. Torres', '', 1);

        self::assertSame(EstadoLlamado::PENDIENTE, $llamado->estado());
        self::assertNull($llamado->tiempoRespuesta());
        self::assertSame(1, $llamado->areaId());
    }

    public function test_paciente_es_opcional(): void
    {
        $repositorio = new FakeLlamadoRepository();
        $registrar = new RegistrarLlamadoUseCase($repositorio, new LlamadoValidator());

        $llamado = $registrar('2026-01-01T10:00', 'Normal', 'Cama', null, 'Enf. Torres', '', 1);

        self::assertNull($llamado->pacienteId());
    }

    public function test_tipo_invalido_es_rechazado(): void
    {
        $repositorio = new FakeLlamadoRepository();
        $registrar = new RegistrarLlamadoUseCase($repositorio, new LlamadoValidator());

        $this->expectException(ValidationException::class);

        $registrar('2026-01-01T10:00', 'TipoQueNoExiste', 'Cama', null, 'Enf. Torres', '', 1);
    }
}
