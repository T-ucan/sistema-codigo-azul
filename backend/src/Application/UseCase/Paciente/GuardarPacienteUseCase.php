<?php

declare(strict_types=1);

namespace CodigoAzul\Application\UseCase\Paciente;

use CodigoAzul\Application\Validation\PacienteValidator;
use CodigoAzul\Domain\Exception\ValidationException;
use CodigoAzul\Domain\Model\Paciente;
use CodigoAzul\Domain\Repository\PacienteRepositoryInterface;

final class GuardarPacienteUseCase
{
    public function __construct(
        private readonly PacienteRepositoryInterface $pacientes,
        private readonly PacienteValidator $validador,
    ) {
    }

    public function __invoke(
        string $nombre,
        string $dni,
        ?string $fechaNacimiento,
        string $datosMedicos,
        ?string $areaIdCrudo,
        ?int $idExistente,
    ): Paciente {
        $nombre = trim($nombre);
        $dni = trim($dni);

        $errores = $this->validador->validar($nombre, $dni, $fechaNacimiento, $areaIdCrudo, $idExistente);
        if ($errores !== []) {
            throw new ValidationException($errores);
        }

        $paciente = new Paciente(
            id: $idExistente,
            nombre: $nombre,
            dni: $dni,
            fechaNacimiento: $fechaNacimiento,
            datosMedicos: trim($datosMedicos),
            areaId: (int) $areaIdCrudo,
        );

        return $this->pacientes->guardar($paciente);
    }
}
