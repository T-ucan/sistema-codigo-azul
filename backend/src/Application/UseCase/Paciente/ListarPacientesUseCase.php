<?php

declare(strict_types=1);

namespace CodigoAzul\Application\UseCase\Paciente;

use CodigoAzul\Domain\Repository\PacienteRepositoryInterface;

final class ListarPacientesUseCase
{
    public function __construct(private readonly PacienteRepositoryInterface $pacientes)
    {
    }

    /** @return \CodigoAzul\Domain\Model\Paciente[] */
    public function __invoke(): array
    {
        return $this->pacientes->listar();
    }
}
